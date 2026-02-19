<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VoiceCommandController;
use App\Models\TtsMessage;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/voice-command', [VoiceCommandController::class, 'handle']);

Route::get('/tts/next', function () {
    // Берем самое старое неозвученное сообщение
    $tts = TtsMessage::oldest()->first();
    
    if ($tts) {
        $data = [
            'status' => 'success', 
            'username' => $tts->username, 
            'message' => $tts->message
        ];
        $tts->delete(); // Удаляем, чтобы не озвучить дважды
        return response()->json($data);
    }
    
    return response()->json(['status' => 'empty']);
});