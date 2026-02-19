<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TtsMessage extends Model
{
    protected $fillable = ['username', 'message'];
}