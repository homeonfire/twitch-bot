<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\TtsMessage;
use App\Models\TwitchBot;

Route::get('/tts/{channel}/next', function ($channel) {
    // Берем самое старое сообщение ТОЛЬКО для запрошенного канала
    $message = TtsMessage::where('channel', $channel)->oldest()->first();
    
    if ($message) {
        $data = [
            'status' => 'success', 
            'username' => $message->username, 
            'message' => $message->message
        ];
        
        $message->delete(); 
        
        return response()->json($data);
    }

    return response()->json(['status' => 'empty']);
});

// 1. Отдаем настройки бота браузеру (чтобы узнать кодовое слово)
Route::get('/voice/{channel}/settings', function ($channel) {
    $bot = TwitchBot::where('twitch_channel', $channel)->where('is_active', true)->first();
    
    if (!$bot) {
        return response()->json(['error' => 'Бот не найден'], 404);
    }

    return response()->json([
        'wake_word' => mb_strtolower($bot->wake_word)
    ]);
});

// 2. Принимаем текст от стримера, спрашиваем DeepSeek и кидаем в TTS
Route::post('/voice/{channel}/ask', function (Request $request, $channel) {
    $bot = TwitchBot::where('twitch_channel', $channel)->where('is_active', true)->first();
    if (!$bot) return response()->json(['error' => 'Бот не найден'], 404);

    $text = $request->input('text');
    $systemPrompt = $bot->system_prompt ?? 'Ты веселый помощник.';

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
            
            // 🚀 Сохраняем ответ в очередь TTS ИМЕННО ЭТОГО канала
            TtsMessage::create([
                'channel' => $channel,
                'username' => $bot->bot_username, // Бот отвечает от своего имени
                'message' => $reply
            ]);

            return response()->json(['status' => 'success']);
        }
    } catch (\Exception $e) {
        Log::error('Voice DeepSeek Error: ' . $e->getMessage());
    }

    return response()->json(['error' => 'Ошибка нейросети'], 500);
});