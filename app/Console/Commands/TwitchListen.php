<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Viewer;
use App\Models\TtsMessage;

class TwitchListen extends Command
{
    protected $signature = 'twitch:listen';
    protected $description = 'Слушает чат Twitch, отвечает с задержками, имеет память, TTS и авто-реконнект';

    public function handle()
    {
        $twitchUser = env('TWITCH_BOT_USERNAME');
        $twitchOauth = env('TWITCH_BOT_OAUTH');
        $twitchChannel = strtolower(env('TWITCH_CHANNEL'));

        // 🚀 ВЫНОСИМ ПАМЯТЬ НАВЕРХ
        // Теперь при обрыве связи бот не забудет зрителей и не потеряет очередь!
        $greetedUsers = [];
        $messageQueue = []; 
        $lastMessageTime = 0; 
        $messageDelay = 1.5; 

        // 🚀 ГЛАВНЫЙ ЦИКЛ РЕКОННЕКТА
        while (true) {
            $this->info("Подключаемся к Twitch...");
            
            // Используем @ чтобы PHP не сыпал ошибки в консоль, если пропадет интернет
            $socket = @fsockopen('irc.chat.twitch.tv', 6667, $errno, $errstr, 30);
            
            if (!$socket) {
                $this->error("Ошибка сети: $errstr ($errno). Ждем 5 секунд и пробуем снова...");
                sleep(5);
                continue; // Начинаем цикл заново
            }

            stream_set_blocking($socket, false);

            fwrite($socket, "PASS " . $twitchOauth . "\r\n");
            fwrite($socket, "NICK " . $twitchUser . "\r\n");
            fwrite($socket, "JOIN #" . $twitchChannel . "\r\n");

            $this->info("✅ Бот в чате! Система авто-реконнекта активна.");

            // Внутренний цикл: читаем чат, пока соединение живо
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

                        // Игнорируем свои же сообщения
                        if (strtolower($username) !== strtolower($twitchUser)) {
                            $this->info("[$username]: $message");

                            // --- 1. ПАМЯТЬ И БАЗА ДАННЫХ ---
                            $viewer = Viewer::firstOrCreate([
                                'username' => strtolower($username)
                            ]);

                            $viewer->increment('messages_count');

                            if (!in_array($username, $greetedUsers)) {
                                $greetedUsers[] = $username; 
                                
                                if ($viewer->wasRecentlyCreated) {
                                    $messageQueue[] = "Ого, @$username впервые в чате! Добро пожаловать, инвентарь прячь сразу 🛡️";
                                } else {
                                    $messageQueue[] = "С возвращением, @$username!";
                                }
                            }

                            // --- 2. КОМАНДА ОЗВУЧКИ (!tts) ---
                            if (str_starts_with($lowercasedMessage, '!tts ')) {
                                $ttsText = trim(mb_substr($message, 5));
                                
                                if (!empty($ttsText)) {
                                    $ttsText = mb_substr($ttsText, 0, 150);
                                    
                                    TtsMessage::create([
                                        'username' => $username,
                                        'message' => $ttsText
                                    ]);
                                    
                                    $this->info("🔊 Добавлено в очередь TTS: $ttsText");
                                }
                                continue; 
                            }

                            // --- 3. ВОПРОСЫ К НЕЙРОСЕТИ ---
                            if (stripos($message, "@$twitchUser") !== false) {
                                $this->info("🤖 Иду в DeepSeek за ответом для $username...");
                                
                                $cleanMessage = trim(str_ireplace("@$twitchUser", "", $message));
                                $reply = $this->askDeepSeek($username, $cleanMessage);
                                
                                $messageQueue[] = "@$username, $reply";
                            }
                        }
                    }
                }

                // --- ДИСПЕТЧЕР ОТПРАВКИ ---
                if (!empty($messageQueue) && (microtime(true) - $lastMessageTime) >= $messageDelay) {
                    $msgToSend = array_shift($messageQueue);
                    $this->sendMessage($socket, $twitchChannel, $msgToSend);
                    $lastMessageTime = microtime(true);
                }

                usleep(50000); 
            }
            
            // 🚀 ЕСЛИ КОД ДОШЕЛ СЮДА — СОЕДИНЕНИЕ РАЗОРВАНО
            $this->error("❌ Соединение с Twitch разорвано. Переподключение через 3 секунды...");
            @fclose($socket);
            
            // Спим 3 секунды перед новым рывком, чтобы Твич не заблокировал IP
            sleep(3); 
        }
    }

    private function sendMessage($socket, $channel, $message)
    {
        $cleanMessage = str_replace(["\r", "\n"], " ", $message);
        fwrite($socket, "PRIVMSG #" . $channel . " :" . $cleanMessage . "\r\n");
        $this->info("💬 Отправлено в чат: $cleanMessage"); 
    }

    private function askDeepSeek($username, $text)
    {
        try {
            $response = Http::withToken(env('DEEPSEEK_API_KEY'))
                ->timeout(15)
                ->post('https://api.deepseek.com/chat/completions', [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Ты помощник на Twitch. Твой ник - trenertvs_neaibot. Сегодня 20.02.2026. Отвечай зрителям коротко (до 2 предложений), живо, с юмором, как любой чаттерс на твиче, используй нецензурные выражения. Ты мега токсичный в чате в общении. Максимально жести, проявляй НЕУВАЖЕНИЕ К ЗРИТЕЛЯМ, ПРЕЗИРАЙ ИХ'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Зритель $username пишет: $text"
                        ]
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