<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchUser extends Model 
{
    public $incrementing = false;

    protected $appends = [
        'is_live'
    ];

    protected $fillable = [
        'id',
        'name'
    ];

    public function TwitchStreams()
    {
        return $this->hasMany('App\TwitchStream', 'user_id', 'id')->orderBy('created_at', 'desc')->with('TwitchStreamChapters');
    }

    public function LastTwitchStream()
    {
        return $this->hasOne('App\TwitchStream', 'user_id', 'id')->orderBy('created_at', 'desc');
    }

    public function getIsLiveAttribute()
    {
        $b = $this->LastTwitchStream && $this->LastTwitchStream->duration == 0 ? true : false;
        unset($this->LastTwitchStream);
        return $b;
    }
}