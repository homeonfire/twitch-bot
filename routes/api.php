<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TtsMessage;
use App\Models\TwitchBot;
use App\Models\OutgoingChatMessage;

// 1. Отдаем сообщения в OBS (с генерацией голоса ElevenLabs или фоллбэком)
Route::get('/tts/{channel}/next', function ($channel) {
    // Берем самое старое сообщение ТОЛЬКО для запрошенного канала
    $message = TtsMessage::where('channel', $channel)->oldest()->first();
    
    if (!$message) {
        return response()->json(['status' => 'empty']);
    }

    $bot = TwitchBot::where('twitch_channel', $channel)->first();
    $audioBase64 = null; // По умолчанию аудио нет, клиент будет читать встроенным голосом

    // Пробуем сгенерировать голос через ElevenLabs, если есть настройки
    if ($bot && $bot->elevenlabs_api_key && $bot->elevenlabs_voice_id) {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $bot->elevenlabs_api_key,
                'Content-Type' => 'application/json'
            ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$bot->elevenlabs_voice_id}", [
                'text' => $message->message,
                'model_id' => 'eleven_multilingual_v2'
            ]);

            if ($response->successful()) {
                // Если всё ок, кодируем MP3 в Base64
                $audioBase64 = 'data:audio/mpeg;base64,' . base64_encode($response->body());
            } else {
                Log::warning("ElevenLabs Error для {$channel}: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("ElevenLabs Exception для {$channel}: " . $e->getMessage());
        }
    }

    $data = [
        'status' => 'success', 
        'username' => $message->username, 
        'message' => $message->message,
        'audio_base64' => $audioBase64 // Отправляем аудио (или null, если была ошибка/нет ключа)
    ];
    
    $message->delete(); 
    
    return response()->json($data);
});

// 2. Отдаем настройки бота браузеру (чтобы узнать кодовое слово)
Route::get('/voice/{channel}/settings', function ($channel) {
    $bot = TwitchBot::where('twitch_channel', $channel)->where('is_active', true)->first();
    
    if (!$bot) {
        return response()->json(['error' => 'Бот не найден'], 404);
    }

    return response()->json([
        'wake_word' => mb_strtolower($bot->wake_word)
    ]);
});

// 3. Принимаем текст от стримера, спрашиваем DeepSeek и кидаем в TTS
Route::post('/voice/{channel}/ask', function (Request $request, $channel) {
    $bot = TwitchBot::where('twitch_channel', $channel)->where('is_active', true)->first();
    if (!$bot) return response()->json(['error' => 'Бот не найден'], 404);

    $text = $request->input('text');
    // Берем промпт для голоса. Если его вдруг нет, используем дефолтный.
    $systemPrompt = $bot->voice_system_prompt ?? 'Ты голосовой ассистент. Отвечай кратко и без смайлов.';

    try {
        // Стучимся в DeepSeek
        $response = Http::withToken(env('DEEPSEEK_API_KEY'))
            ->timeout(15)
            ->post('https://api.deepseek.com/chat/completions', [
                'model' => 'deepseek-chat',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Стример спрашивает тебя голосом: $text"]
                ]
            ]);

        if ($response->successful()) {
            $reply = $response->json('choices.0.message.content');
            
            // Сохраняем в очередь TTS
            TtsMessage::create([
                'channel' => $channel,
                'username' => $bot->bot_username,
                'message' => $reply
            ]);

            // Сохраняем в очередь чата Twitch
            OutgoingChatMessage::create([
                'channel' => $channel,
                'message' => $reply
            ]);

            return response()->json(['status' => 'success']);
        }
    } catch (\Exception $e) {
        Log::error('Voice DeepSeek Error: ' . $e->getMessage());
    }

    return response()->json(['error' => 'Ошибка нейросети'], 500);
});