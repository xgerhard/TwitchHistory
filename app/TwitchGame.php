<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TwitchGame extends Model 
{
    public $incrementing = false;

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'id',
        'name',
        'box_art_url'
    ];
}