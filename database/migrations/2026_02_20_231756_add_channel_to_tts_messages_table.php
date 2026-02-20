<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tts_messages', function (Blueprint $table) {
            $table->string('channel')->after('id')->default('default'); // Добавляем канал
        });
    }

    public function down(): void
    {
        Schema::table('tts_messages', function (Blueprint $table) {
            $table->dropColumn('channel');
        });
    }
};
