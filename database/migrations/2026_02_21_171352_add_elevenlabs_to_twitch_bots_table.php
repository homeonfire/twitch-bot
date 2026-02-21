<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('twitch_bots', function (Blueprint $table) {
            $table->string('elevenlabs_api_key')->nullable()->after('voice_system_prompt')->comment('Ключ от ElevenLabs');
            $table->string('elevenlabs_voice_id')->nullable()->after('elevenlabs_api_key')->comment('ID голоса');
        });
    }

    public function down(): void
    {
        Schema::table('twitch_bots', function (Blueprint $table) {
            $table->dropColumn(['elevenlabs_api_key', 'elevenlabs_voice_id']);
        });
    }
};