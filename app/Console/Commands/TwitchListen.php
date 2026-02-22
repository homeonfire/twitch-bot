<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Viewer;
use App\Models\TtsMessage;
use App\Models\TwitchBot;
use App\Models\OutgoingChatMessage;
use App\Models\ChatMessage;
use App\Jobs\VectorizeChatMessage;

class TwitchListen extends Command
{
    // 🚀 ТЕПЕРЬ КОМАНДА ТРЕБУЕТ ID БОТА ИЗ БАЗЫ
    protected $signature = 'twitch:listen {bot_id}';
    protected $description = 'Запускает конкретного бота из базы данных по его ID';

    public function handle()
    {
        $botId = $this->argument('bot_id');
        $botConfig = TwitchBot::find($botId);

        if (!$botConfig || !$botConfig->is_active) {
            $this->error("Бот с ID {$botId} не найден или отключен в админке!");
            return;
        }

        // Берем настройки из базы
        $twitchUser = strtolower($botConfig->bot_username);
        $twitchOauth = $botConfig->bot_oauth;
        $twitchChannel = strtolower($botConfig->twitch_channel);
        $systemPrompt = $botConfig->system_prompt ?? 'Ты веселый помощник стримера на Twitch.';

        $this->info("Запускаем бота [{$twitchUser}] для канала [#{$twitchChannel}]...");

        $greetedUsers = [];
        $messageQueue = []; 
        $lastMessageTime = 0; 
        $messageDelay = 1.5; 
        $ignoredUsers = ['nightbot', 'streamelements', 'streamlabs', 'moobot', 'fossabot','trenertvs_neaibot','arcyfor'];

        // 🚀 ТАЙМЕР ДЛЯ ПРОВЕРКИ БАЗЫ
        $lastDbCheckTime = 0;

        while (true) {
            // Проверяем, не выключили ли бота через админку прямо во время работы
            $botConfig->refresh();
            if (!$botConfig->is_active) {
                $this->warn("Бот был отключен в админке. Завершаю работу.");
                break;
            }

            $socket = @fsockopen('irc.chat.twitch.tv', 6667, $errno, $errstr, 30);
            
            if (!$socket) {
                $this->error("Ошибка сети. Реконнект через 5 сек...");
                sleep(5);
                continue; 
            }

            stream_set_blocking($socket, false);
            fwrite($socket, "PASS " . $twitchOauth . "\r\n");
            fwrite($socket, "NICK " . $twitchUser . "\r\n");
            fwrite($socket, "JOIN #" . $twitchChannel . "\r\n");

            $this->info("✅ Бот {$twitchUser} подключен к #{$twitchChannel}!");

            while (!feof($socket)) {
                $line = fgets($socket, 1024);
                
                if ($line) {
                    if (strpos($line, 'PING') === 0) {
                        fwrite($socket, "PONG :tmi.twitch.tv\r\n");
                        continue;
                    }

                    if (preg_match('/^:(.*?)!.*?PRIVMSG #(.*?) :(.*)$/', $line, $matches)) {
                        $username = $matches[1];
                        $message = trim($matches[3]);
                        $lowercasedMessage = mb_strtolower($message);
                        $lowerUsername = strtolower($username);

                        if ($lowerUsername === $twitchUser || in_array($lowerUsername, $ignoredUsers)) {
                            continue;
                        }

                        $this->info("[#{$twitchChannel}] {$username}: {$message}");

                        $viewer = Viewer::firstOrCreate([
                            'channel' => $twitchChannel,
                            'username' => $lowerUsername
                        ]);
                        $viewer->increment('messages_count');
                        // 🚀 СОХРАНЯЕМ КАЖДОЕ СООБЩЕНИЕ В ИСТОРИЮ
                        ChatMessage::create([
                            'channel' => $twitchChannel,
                            'username' => $username,
                            'message' => $message
                        ]);

                        // 🚀 ДОБАВЛЯЕМ ВОТ ЭТО: Отправляем сообщение на векторизацию в фон!
                        VectorizeChatMessage::dispatch($twitchChannel, $username, $message);

                        if (!in_array($username, $greetedUsers)) {
                            $greetedUsers[] = $username; 
                            if ($viewer->wasRecentlyCreated) {
                                $messageQueue[] = "Ого, @$username впервые на канале! Добро пожаловать!";
                            } else {
                                $messageQueue[] = "С возвращением, @$username!";
                            }
                        }

                        // 🚀 ДОБАВЛЯЕМ TTS С ПРИВЯЗКОЙ К КАНАЛУ
                        if (str_starts_with($lowercasedMessage, '!tts ')) {
                            $ttsText = trim(mb_substr($message, 5));
                            if (!empty($ttsText)) {
                                TtsMessage::create([
                                    'channel' => $twitchChannel, // <-- Сохраняем канал!
                                    'username' => $username,
                                    'message' => mb_substr($ttsText, 0, 150)
                                ]);
                                $this->info("🔊 Добавлено в очередь TTS для {$twitchChannel}");
                            }
                            continue; 
                        }

                        // 🚀 ПЕРЕДАЕМ ИНДИВИДУАЛЬНЫЙ ПРОМПТ
                        if (stripos($message, "@$twitchUser") !== false) {
                            $cleanMessage = trim(str_ireplace("@$twitchUser", "", $message));
                            // Добавили $twitchChannel четвертым аргументом
                            $reply = $this->askDeepSeek($username, $cleanMessage, $systemPrompt, $twitchChannel); 
                            $messageQueue[] = "@$username, $reply";
                        }
                    }
                }

                // 🚀 ПРОВЕРЯЕМ БАЗУ РАЗ В 2 СЕКУНДЫ НА НАЛИЧИЕ НОВЫХ ОТВЕТОВ ДЛЯ ЧАТА
                if (microtime(true) - $lastDbCheckTime >= 2.0) {
                    // 1. Проверяем, не выключили ли бота в админке
                    $botConfig->refresh();
                    if (!$botConfig->is_active) {
                        $this->warn("Бот отключен в админке. Закрываю соединение...");
                        @fclose($socket); // Закрываем сокет
                        return; // Полностью выходим из команды и завершаем процесс!
                    }

                    // 2. Ищем сообщения из базы для отправки в чат
                    $outgoing = OutgoingChatMessage::where('channel', $twitchChannel)->oldest()->first();
                    if ($outgoing) {
                        $this->info("📥 Отправляю в чат: {$outgoing->message}");
                        $messageQueue[] = $outgoing->message;
                        $outgoing->delete();
                    }
                    
                    $lastDbCheckTime = microtime(true);
                }


                if (!empty($messageQueue) && (microtime(true) - $lastMessageTime) >= $messageDelay) {
                    $msgToSend = array_shift($messageQueue);
                    $this->sendMessage($socket, $twitchChannel, $msgToSend);
                    $lastMessageTime = microtime(true);
                }

                usleep(50000); 
            }
            
            @fclose($socket);
            sleep(3); 
        }
    }

    private function sendMessage($socket, $channel, $message)
    {
        $cleanMessage = str_replace(["\r", "\n"], " ", $message);
        fwrite($socket, "PRIVMSG #" . $channel . " :" . $cleanMessage . "\r\n");
    }

    // 🚀 ДОБАВИЛИ $channel В АРГУМЕНТЫ
    private function askDeepSeek($username, $text, $systemPrompt, $channel)
    {
        $hfToken = env('HF_TOKEN');
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_KEY');

        // 1. ИЩЕМ СМЫСЛЫ: Получаем вектор текущего вопроса
        $questionEmbedding = Http::withToken($hfToken)
            ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-small/pipeline/feature-extraction', [
                'inputs' => [$text]
            ])->json()[0] ?? null;

        $memoryContext = "";

        // 2. ДОЛГОСРОЧНАЯ ПАМЯТЬ: Ищем релевантные воспоминания в Supabase
        if ($questionEmbedding) {
            $searchResponse = Http::withHeaders([
                'apikey' => $supabaseKey,
                'Authorization' => 'Bearer ' . $supabaseKey,
                'Content-Type' => 'application/json'
            ])->post("{$supabaseUrl}/rest/v1/rpc/match_messages", [
                'query_embedding' => $questionEmbedding,
                'match_threshold' => 0.7, // Берем только уверенные совпадения (от 70%)
                'match_count' => 5,       // Топ-5 фактов
                'p_channel' => $channel,
                'p_username' => $username
            ]);

            if ($searchResponse->successful() && count($searchResponse->json()) > 0) {
                $memoryContext = "Вот релевантные факты из прошлых диалогов с этим зрителем (долгосрочная память):\n";
                foreach ($searchResponse->json() as $mem) {
                    $memoryContext .= "- {$mem['content']}\n";
                }
            }
        }

        // 3. КРАТКОСРОЧНАЯ ПАМЯТЬ: Берем последние 5 сообщений из чата (чтобы не терять нить разговора)
        $recentHistory = \App\Models\ChatMessage::where('channel', $channel)
            ->latest()
            ->take(5)
            ->get()
            ->reverse();

        $recentContext = "Последние сообщения из чата (краткосрочная память):\n";
        foreach ($recentHistory as $msg) {
            $recentContext .= "{$msg->username}: {$msg->message}\n";
        }

        // 4. ФОРМИРУЕМ МЕГА-ПРОМПТ
        $finalContext = $memoryContext . "\n" . $recentContext . "\nА теперь ответь пользователю {$username} на его сообщение: {$text}";

        try {
            // Спрашиваем саму нейросеть
            $response = Http::withToken(env('DEEPSEEK_API_KEY'))
                ->timeout(15)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $finalContext]
                    ]
                ]);

            if ($response->successful()) {
                $reply = $response->json('choices.0.message.content');

                // 🚀 5. ЗАПИСЫВАЕМ НОВЫЙ ОПЫТ В ДОЛГОСРОЧНУЮ ПАМЯТЬ
                // Склеиваем вопрос и ответ, чтобы бот запомнил контекст диалога
                $memoryText = "Зритель {$username} сказал: \"{$text}\". Бот ответил: \"{$reply}\".";
                
                $memoryEmbedding = Http::withToken($hfToken)
                    ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-small/pipeline/feature-extraction', [
                        'inputs' => [$memoryText]
                    ])->json()[0] ?? null;

                if ($memoryEmbedding) {
                    Http::withHeaders([
                        'apikey' => $supabaseKey,
                        'Authorization' => 'Bearer ' . $supabaseKey,
                        'Content-Type' => 'application/json'
                    ])->post("{$supabaseUrl}/rest/v1/chat_embeddings", [
                        'channel' => $channel,
                        'username' => $username,
                        'content' => $memoryText,
                        'embedding' => $memoryEmbedding
                    ]);
                }

                return $reply;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('DeepSeek/Vector Error: ' . $e->getMessage());
        }
        
        return "Нейроны заискрили. Спроси чуть позже!";
    }
}