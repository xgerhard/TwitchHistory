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
}