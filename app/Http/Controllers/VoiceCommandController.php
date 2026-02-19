<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // Подключаем класс для HTTP-запросов

class VoiceCommandController extends Controller
{
    public function handle(Request $request)
    {
        // 1. Получаем то, что ты сказал из OBS
        $text = $request->input('command');
        Log::info('🗣️ Я сказал: ' . $text);

        // Убираем слово "бот" в начале и переводим в нижний регистр для удобства
        $userMessage = mb_strtolower(trim(preg_replace('/^тренер[, ]*/ui', '', $text)));
        
        if (empty($userMessage)) {
            return response()->json(['status' => 'ignored']);
        }

        // 🚀 НОВОВВЕДЕНИЕ: ПЕРЕХВАТ ГОЛОСОВЫХ КОМАНД
        // Если ты сказал ключевые слова, бот сразу готовит ссылку
        if (str_contains($userMessage, 'скинь') && str_contains($userMessage, 'дискорд')||str_contains($userMessage, 'discord')) {
            Log::info('⚡ Сработала голосовая команда: Discord');
            
            // Вставь сюда свою реальную ссылку
            $discordLink = "https://discord.gg/qSRuFADGcD"; 
            $aiReply = "Залетайте в наш Discord, работяги: " . $discordLink;
            
        } else {
            // Если это обычный вопрос, отправляем его в DeepSeek
            Log::info('🧠 Отправляю в DeepSeek...');
            
            try {
                $response = Http::withToken(env('DEEPSEEK_API_KEY'))
                    ->timeout(15)
                    ->post('https://api.deepseek.com/chat/completions', [
                        'model' => 'deepseek-chat',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'Ты саркастичный, но полезный ИИ-ассистент на Twitch-стриме. Стример играет разные игры и общается. Отвечай зрителям коротко (до 2 предложений), живо, с юмором, как любой чаттерс на твиче, используй нецензурные выражения.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $userMessage
                            ]
                        ]
                    ]);

                if ($response->successful()) {
                    $aiReply = $response->json('choices.0.message.content');
                } else {
                    $aiReply = "Ой, мои нейросети запутались. Повтори-ка!";
                }
            } catch (\Exception $e) {
                Log::error('Ошибка соединения с ИИ: ' . $e->getMessage());
                $aiReply = "Связь с космосом прервана, попробуй позже.";
            }
        }

        // 3. Отправляем готовый ответ (ссылку или текст от ИИ) в чат Twitch
        $twitchUser = env('TWITCH_BOT_USERNAME');
        $twitchOauth = env('TWITCH_BOT_OAUTH');
        $twitchChannel = strtolower(env('TWITCH_CHANNEL')); 

        $socket = @fsockopen('irc.chat.twitch.tv', 6667, $errno, $errstr, 5);

        if ($socket) {
            fwrite($socket, "PASS " . $twitchOauth . "\r\n");
            fwrite($socket, "NICK " . $twitchUser . "\r\n");
            
            $cleanReply = str_replace(["\r", "\n"], " ", $aiReply);
            fwrite($socket, "PRIVMSG #" . $twitchChannel . " :" . $cleanReply . "\r\n");
            fclose($socket);
        }

        return response()->json(['status' => 'success']);
    }
}