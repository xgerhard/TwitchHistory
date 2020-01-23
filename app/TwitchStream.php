<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchStream extends Model 
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'created_at',
        'user_id',
        'title',
        'duration',
        'vod_id'
    ];

    public function TwitchStreamChapters()
    {
        return $this->hasMany('App\TwitchStreamChapter', 'stream_id', 'id')->with('TwitchGame');
    }
}