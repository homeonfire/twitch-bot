<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TwitchBot extends Model
{
    protected $fillable = [
        'bot_username',
        'bot_oauth',
        'twitch_channel',
        'wake_word',
        'system_prompt',       // Это для текстового чата
        'voice_system_prompt', // 🚀 А это для голоса
        'is_active',
    ];
}