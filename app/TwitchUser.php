<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchUser extends Model 
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name'
    ];

    public function TwitchStreams()
    {
        return $this->hasMany('App\TwitchStream', 'user_id', 'id')->orderBy('created_at', 'desc')->with('TwitchStreamChapters');
    }
}