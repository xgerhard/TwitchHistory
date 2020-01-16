<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchGame extends Model 
{
    protected $fillable = [
        'id',
        'name',
        'box_art_url'
    ];
}