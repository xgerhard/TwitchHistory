<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class StreamSession extends Model 
{
    protected $fillable = [
        'user_id',
        'started_at',
        'game_id',
        'finished',
        'stream_id',
        'stream_reference'
    ];
}