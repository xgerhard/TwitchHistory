<?php

namespace App\Http\Controllers;

use App\TwitchUser;
use Illuminate\Http\Response;

class ChannelController extends Controller
{
    public function index()
    {
        return response(TwitchUser::orderBy('name', 'ASC')->with('LastTwitchStream')->get()->jsonSerialize(), Response::HTTP_OK);
    }

    public function show($id)
    {
        return response(
            TwitchUser::with(['TwitchStreams' => function($q) {
                // where $q->where(x, y)
                // offset $q->offset(offset * limit)
                return $q->limit(10);
            }])
            ->find($id)->jsonSerialize(),
            Response::HTTP_OK
        );
    }
}