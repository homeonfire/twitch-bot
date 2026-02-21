<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TtsMessage;
use App\Models\TwitchBot;
use App\Models\OutgoingChatMessage;

// 1. Отдаем сообщения в OBS (с генерацией голоса ElevenLabs или фоллбэком)
// 1. Отдаем сообщения в OBS (с генерацией голоса ElevenLabs, Google или фоллбэком)
Route::get('/tts/{channel}/next', function ($channel) {
    $message = TtsMessage::where('channel', $channel)->oldest()->first();
    
    if (!$message) {
        return response()->json(['status' => 'empty']);
    }

    $bot = TwitchBot::where('twitch_channel', $channel)->first();
    $audioBase64 = null; 
    $audioUrl = null; // 🚀 Сюда положим ссылку на Гугл

    // 1. Пробуем сгенерировать голос через ElevenLabs
    if ($bot && $bot->elevenlabs_api_key && $bot->elevenlabs_voice_id) {
        try {
            $response = Http::withHeaders([
                'xi-api-key' => $bot->elevenlabs_api_key,
                'Content-Type' => 'application/json'
            ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$bot->elevenlabs_voice_id}", [
                'text' => $message->message,
                'model_id' => 'eleven_turbo_v2_5',
                'language_code' => 'ru',
            ]);

            if ($response->successful()) {
                $audioBase64 = 'data:audio/mpeg;base64,' . base64_encode($response->body());
            } else {
                Log::warning("ElevenLabs Error: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("ElevenLabs Exception: " . $e->getMessage());
        }
    }

    // 🚀 2. Если ElevenLabs не сработал (нет ключа или токенов) — подключаем Google Translate!
    if (!$audioBase64) {
        try {
            $safeText = urlencode(mb_substr($message->message, 0, 200));
            // Используем client=gtx, он работает стабильнее для API запросов
            $googleUrl = "https://translate.googleapis.com/translate_tts?ie=UTF-8&client=gtx&tl=ru&q={$safeText}";
            
            // Обязательно притворяемся браузером Windows, иначе Гугл отдаст 403 ошибку
            $googleResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ])->get($googleUrl);

            if ($googleResponse->successful()) {
                // Кодируем mp3 от Гугла точно так же, как от ElevenLabs!
                $audioBase64 = 'data:audio/mpeg;base64,' . base64_encode($googleResponse->body());
            } else {
                Log::warning("Google TTS Error: HTTP " . $googleResponse->status());
            }
        } catch (\Exception $e) {
            Log::error("Google TTS Exception: " . $e->getMessage());
        }
    }

    $data = [
        'status' => 'success', 
        'username' => $message->username, 
        'message' => $message->message,
        'audio_base64' => $audioBase64,
        'audio_url' => $audioUrl // 🚀 Передаем ссылку на гугл в браузер
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