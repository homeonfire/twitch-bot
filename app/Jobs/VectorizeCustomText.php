<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VectorizeCustomText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $channel;
    public $username;
    public $text;

    public function __construct($channel, $username, $text)
    {
        $this->channel = $channel;
        $this->username = $username;
        $this->text = $text;
    }

    public function handle(): void
    {
        $hfToken = env('HF_TOKEN');
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_KEY');

        try {
            $hfResponse = Http::withToken($hfToken)
                ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-small/pipeline/feature-extraction', [
                    'inputs' => [$this->text]
                ]);

            if ($hfResponse->successful() && isset($hfResponse->json()[0])) {
                $embedding = $hfResponse->json()[0];

                Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => 'Bearer ' . $supabaseKey,
                    'Content-Type' => 'application/json'
                ])->post("{$supabaseUrl}/rest/v1/chat_embeddings", [
                    'channel' => $this->channel,
                    'username' => $this->username,
                    'content' => $this->text,
                    'embedding' => $embedding
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Сбой VectorizeCustomText: " . $e->getMessage());
        }
    }
}