<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('viewers', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique(); // Ник зрителя (уникальный)
            $table->integer('messages_count')->default(0); // Счетчик сообщений
            $table->integer('trust_factor')->default(100); // Уровень доверия (по умолчанию 100)
            $table->timestamps(); // Автоматически запишет время первого появления
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viewers');
    }
};
