<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\TtsMessage;

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