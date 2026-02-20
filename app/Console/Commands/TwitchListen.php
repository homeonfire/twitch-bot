<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Viewer;
use App\Models\TtsMessage;
use App\Models\TwitchBot;

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
        $ignoredUsers = ['nightbot', 'streamelements', 'streamlabs', 'moobot', 'fossabot'];

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

                        $viewer = Viewer::firstOrCreate(['username' => $lowerUsername]);
                        $viewer->increment('messages_count');

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
                                $messageQueue[] = "@$username, улетело на озвучку!";
                            }
                            continue; 
                        }

                        // 🚀 ПЕРЕДАЕМ ИНДИВИДУАЛЬНЫЙ ПРОМПТ
                        if (stripos($message, "@$twitchUser") !== false) {
                            $cleanMessage = trim(str_ireplace("@$twitchUser", "", $message));
                            $reply = $this->askDeepSeek($username, $cleanMessage, $systemPrompt);
                            $messageQueue[] = "@$username, $reply";
                        }
                    }
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

    private function askDeepSeek($username, $text, $systemPrompt)
    {
        try {
            $response = Http::withToken(env('DEEPSEEK_API_KEY'))
                ->timeout(15)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt], // Используем промпт из БД
                        ['role' => 'user', 'content' => "Зритель $username пишет: $text"]
                    ]
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }
        } catch (\Exception $e) {
            Log::error('DeepSeek Error: ' . $e->getMessage());
        }
        return "Нейроны заискрили. Спроси чуть позже!";
    }
}