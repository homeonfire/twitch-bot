<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VectorizeChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $channel;
    public $username;
    public $message;

    public function __construct($channel, $username, $message)
    {
        $this->channel = $channel;
        $this->username = $username;
        $this->message = $message;
    }

    public function handle(): void
    {
        $hfToken = env('HF_TOKEN');
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_KEY');

        // Подготавливаем текст для лора
        $memoryText = "Зритель {$this->username} написал в чат: \"{$this->message}\"";

        try {
            // 1. Получаем вектор из Hugging Face
            $hfResponse = Http::withToken($hfToken)
                ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-small/pipeline/feature-extraction', [
                    'inputs' => [$memoryText]
                ]);

            if ($hfResponse->successful()) {
                $embedding = $hfResponse->json()[0] ?? null;

                if ($embedding) {
                    // 2. Сохраняем вектор в Supabase
                    Http::withHeaders([
                        'apikey' => $supabaseKey,
                        'Authorization' => 'Bearer ' . $supabaseKey,
                        'Content-Type' => 'application/json'
                    ])->post("{$supabaseUrl}/rest/v1/chat_embeddings", [
                        'channel' => $this->channel,
                        'username' => $this->username,
                        'content' => $memoryText,
                        'embedding' => $embedding
                    ]);
                }
            } else {
                Log::warning("Ошибка генерации вектора в фоне: " . $hfResponse->body());
            }
        } catch (\Exception $e) {
            Log::error("Сбой в фоновой задаче VectorizeChatMessage: " . $e->getMessage());
        }
    }
}