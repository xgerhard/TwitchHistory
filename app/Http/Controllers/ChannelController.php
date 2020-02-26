<?php

namespace App\Http\Controllers;

use App\TwitchUser;
use Illuminate\Http\Response;

class ChannelController extends Controller
{
    public function index()
    {
        return response(TwitchUser::get()->jsonSerialize(), Response::HTTP_OK);
    }

    public function show($id)
    {
        return response(TwitchUser::with('TwitchStreams')->find($id)->jsonSerialize(), Response::HTTP_OK);
    }
}