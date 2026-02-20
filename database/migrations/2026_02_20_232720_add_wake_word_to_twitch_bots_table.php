<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('twitch_bots', function (Blueprint $table) {
            $table->string('wake_word')->default('бот')->after('twitch_channel')->comment('Слово для активации голосом');
        });
    }

    public function down(): void
    {
        Schema::table('twitch_bots', function (Blueprint $table) {
            $table->dropColumn('wake_word');
        });
    }
};
