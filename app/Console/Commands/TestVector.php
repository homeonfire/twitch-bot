<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestVector extends Command
{
    protected $signature = 'app:test-vector';
    protected $description = 'Тестирование генерации векторов и записи в Supabase';

    public function handle()
    {
        $this->info("🚀 Начинаем тест векторной базы...");

        $channel = 'trenertvs';
        $username = 'test_viewer';
        
        // 1. Текст, который мы хотим запомнить
        $messageToRemember = "На эко-раунде всегда беру P250 и флешку, это лучшая тактика.";
        $this->info("1. Текст для памяти: " . $messageToRemember);

        // 2. Идем в Hugging Face за вектором
        $this->info("2. Получаем вектор из Hugging Face...");
        $hfToken = env('HF_TOKEN');
        $hfResponse = Http::withToken($hfToken)
            ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-small/pipeline/feature-extraction', [
                'inputs' => [$messageToRemember]
            ]);

        if (!$hfResponse->successful()) {
            $this->error("Ошибка Hugging Face: " . $hfResponse->body());
            return;
        }

        // HF возвращает массив массивов, берем первый
        $embedding = $hfResponse->json()[0]; 
        $this->info("✅ Вектор получен! Размер: " . count($embedding) . " цифр.");

        // 3. Сохраняем в Supabase
        $this->info("3. Сохраняем вектор в Supabase...");
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_KEY');

        $insertResponse = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
            'Content-Type' => 'application/json'
        ])->post("{$supabaseUrl}/rest/v1/chat_embeddings", [
            'channel' => $channel,
            'username' => $username,
            'content' => $messageToRemember,
            'embedding' => $embedding // Передаем массив цифр
        ]);

        if (!$insertResponse->successful()) {
            $this->error("Ошибка записи в Supabase: " . $insertResponse->body());
            return;
        }
        $this->info("✅ Сообщение успешно сохранено в долгосрочную память!");

        // 4. ТЕСТИРУЕМ ПОИСК (Задаем похожий вопрос)
        $question = "Какую пушку посоветуешь на эко?";
        $this->warn("\n❓ Задаем вопрос: " . $question);
        
        // Получаем вектор для вопроса
        $questionEmbedding = Http::withToken($hfToken)
            ->post('https://router.huggingface.co/hf-inference/models/intfloat/multilingual-e5-small/pipeline/feature-extraction', [
                'inputs' => [$question]
            ])->json()[0];

        // Ищем в Supabase через нашу функцию match_messages
        $searchResponse = Http::withHeaders([
            'apikey' => $supabaseKey,
            'Authorization' => 'Bearer ' . $supabaseKey,
            'Content-Type' => 'application/json'
        ])->post("{$supabaseUrl}/rest/v1/rpc/match_messages", [
            'query_embedding' => $questionEmbedding,
            'match_threshold' => 0.7, // Ищем совпадения с уверенностью от 70%
            'match_count' => 3,       // Вернуть топ-3 результата
            'p_channel' => $channel,
            'p_username' => $username
        ]);

        if ($searchResponse->successful()) {
            $results = $searchResponse->json();
            $this->info("\n🔍 Найдено в памяти (" . count($results) . " совпадений):");
            foreach ($results as $res) {
                // Выводим текст и процент совпадения
                $percent = round($res['similarity'] * 100, 1);
                $this->line("- [{$percent}%] {$res['content']}");
            }
        } else {
            $this->error("Ошибка поиска: " . $searchResponse->body());
        }
    }
}