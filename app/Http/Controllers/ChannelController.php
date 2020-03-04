<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\TwitchUser;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

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

    public function stats(Request $request, $iChannelId)
    { 
        $strTimeFrame = 'week';
        $aTimeFrames = ['week', 'month', '6-month', 'year'];

        if($request->has('period') && in_array($request->get('period'), $aTimeFrames))
            $strTimeFrame = $request->get('period');

        switch($strTimeFrame)
        {
            default:
            case 'weeK':
                $dFrom = Carbon::now()->subWeek();
            break;

            case 'month':
                $dFrom = Carbon::now()->subMonth();
            break;

            case '6-month':
                $dFrom = Carbon::now()->subQuarters(2);
            break;

            case 'year':
                $dFrom = Carbon::now()->subYear();
            break;
        }

        $oUser = TwitchUser::with(['TwitchStreams' => function($q) use ($dFrom) {
            return $q->where('created_at', '>', $dFrom);
        }])
        ->find($iChannelId);

        $aGames = [];
        $iTotalDuration = 0;
        $aStreamDays = [];

        if($oUser->TwitchStreams->count() > 0)
        {
            foreach($oUser->TwitchStreams as $oStream)
            {
                $iTotalDuration += $oStream->duration;

                if($oStream->TwitchStreamChapters && $oStream->TwitchStreamChapters->count() > 0)
                {
                    foreach($oStream->TwitchStreamChapters as $oChapter)
                    {
                        if(isset($aGames[$oChapter->game_id]))
                        {
                            $aGames[$oChapter->game_id]->duration += $oChapter->duration;
                        }
                        else
                        {
                            $aGames[$oChapter->game_id] = (object)[
                                'duration' => $oChapter->duration,
                                'game' => $oChapter->TwitchGame
                            ];
                        }
                    }
                }
            }
        }

        $aStats = [
            'stream_time' => $iTotalDuration,
            'streams' => $oUser->TwitchStreams->count(),
            'avg_stream_time' => ($oUser->TwitchStreams->count() > 0 ? round($iTotalDuration / $oUser->TwitchStreams->count()) : 0),
            'top_games' => array_slice($this->sortProperty($aGames, 'duration'), 0, 100)
        ];

        $oUser->stats = $aStats;

        // We needed the TwitchStreams to calc the stats, dont have to return them
        unset($oUser->TwitchStreams);

        return response($oUser->jsonSerialize(), Response::HTTP_OK);
    }

    public function sortProperty($array, $property, $asc = false)
    {
        usort($array, function($first, $second) use ($property, $asc){
            return $asc ? $first->{$property} > $second->{$property} : $first->{$property} < $second->{$property};
        });
        return $array;
    }
}