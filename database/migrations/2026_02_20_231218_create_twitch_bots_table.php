<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twitch_bots', function (Blueprint $table) {
            $table->id();
            $table->string('bot_username')->comment('Ник самого бота (например: my_cool_bot)');
            $table->string('bot_oauth')->comment('Токен oauth:...');
            $table->string('twitch_channel')->comment('Канал, который слушаем (без #)');
            $table->text('system_prompt')->nullable()->comment('Промпт для DeepSeek');
            $table->boolean('is_active')->default(true)->comment('Включен ли бот');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twitch_bots');
    }
};