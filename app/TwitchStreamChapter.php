<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchStreamChapter extends Model 
{
    protected $fillable = [
        'created_at',
        'updated_at',
        'stream_id',
        'game_id',
        'duration'
    ];

    public function TwitchStream()
    {
        return $this->hasOne('App\TwitchStream', 'id', 'stream_id');
    }

    public function TwitchGame()
    {
        return $this->hasOne('App\TwitchGame', 'id', 'game_id');
    }
}