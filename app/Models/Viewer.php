<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Viewer extends Model
{
    // Разрешаем массовое заполнение этих колонок
    protected $fillable = ['username', 'messages_count', 'trust_factor'];
}