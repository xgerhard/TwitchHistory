<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchGame extends Model 
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'box_art_url'
    ];
}