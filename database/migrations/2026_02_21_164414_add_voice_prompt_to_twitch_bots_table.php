<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('twitch_bots', function (Blueprint $table) {
            $table->text('voice_system_prompt')
                  ->nullable()
                  ->after('system_prompt')
                  ->comment('Инструкция для ответов голосом стримеру');
        });
    }

    public function down(): void
    {
        Schema::table('twitch_bots', function (Blueprint $table) {
            $table->dropColumn('voice_system_prompt');
        });
    }
};