<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Viewer extends Model
{
    protected $fillable = ['channel', 'username', 'messages_count'];

    // 🚀 ДОБАВЛЯЕМ СВЯЗЬ С СООБЩЕНИЯМИ
    public function chatMessages()
    {
        return $this->hasMany(ChatMessage::class, 'username', 'username')
                    ->where('channel', $this->channel);
    }
}