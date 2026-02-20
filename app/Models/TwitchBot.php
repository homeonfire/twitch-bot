<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TwitchBot extends Model
{
    protected $fillable = [
        'bot_username',
        'bot_oauth',
        'twitch_channel',
        'system_prompt',
        'is_active',
        'wake_word',
    ];
}