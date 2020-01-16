<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model 
{
    protected $fillable = [
        'topic',
        'lease_seconds',
        'secret',
        'expires_at'
    ];
}