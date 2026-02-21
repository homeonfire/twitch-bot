<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Удаляем старую кривую таблицу со смешанной статой
        Schema::dropIfExists('viewers');

        // 2. Создаем новую, правильную
        Schema::create('viewers', function (Blueprint $table) {
            $table->id();
            $table->string('channel')->comment('Канал, где сидит зритель');
            $table->string('username')->comment('Ник зрителя на Twitch');
            $table->integer('messages_count')->default(0);
            $table->timestamps();

            // 🚀 ГЛАВНОЕ: Уникальным является не просто ник, а связка "Канал + Ник"
            $table->unique(['channel', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viewers');
    }
};