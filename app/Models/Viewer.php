<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Viewer extends Model
{
    // 🚀 Добавили 'channel' в начало
    protected $fillable = ['channel', 'username', 'messages_count']; 
}