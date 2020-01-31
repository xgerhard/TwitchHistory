<?php

namespace App\Http\Controllers;

use App\TwitchStream;
use App\TwitchAPI;
use App\TwitchUser;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;

class StatsController extends Controller
{
    public function index(Request $request, $iUserId)
    {
        $dNow = Carbon::now();
        $iTotalStreamDuration = 0;
        $aGames = [];

        $oUser = TwitchUser::with('TwitchStreams')->findOrFail($iUserId);
        if($oUser->TwitchStreams->count() > 0)
        {
            foreach($oUser->TwitchStreams as $oStream)
            {
                $aStreamGames = [];

                // Calc total stream duration
                $iStreamDuration = $oStream->duration == 0 ? $dNow->diffInSeconds($oStream->created_at) : $oStream->duration;
                $iTotalStreamDuration += $iStreamDuration;
                $oStream->durationTime = $this->secondsToTime($iStreamDuration, true);
                
                if($oStream->TwitchStreamChapters && $oStream->TwitchStreamChapters->count() > 0)
                {
                    foreach($oStream->TwitchStreamChapters as $oChapter)
                    {
                        // Set Vod timestamp
                        $strVodUrl = null;
                        if($oStream->vod_id)
                        {
                            $strVodUrl = 'https://www.twitch.tv/videos/'. $oStream->vod_id;
                            if($oStream->created_at != $oChapter->created_at)
                            {
                                $iDurationFromStart = $oChapter->created_at->diffInSeconds($oStream->created_at);
                                if($iDurationFromStart > 0)
                                {
                                    $strVodTimeStamp = $this->secondsToVodTimeStamp($iDurationFromStart);
                                    if($strVodTimeStamp)
                                        $strVodUrl .= '?t='. $strVodTimeStamp;
                                }
                            }
                        }
                        $oChapter->vodUrl = $strVodUrl;

                        // Calc total chapter duration
                        $iChapterDuration = $oChapter->duration == 0 ? $dNow->diffInSeconds($oChapter->created_at) : $oChapter->duration;
                        $oChapter->durationTime = $this->secondsToTime($iChapterDuration, true);

                        // Calc game duration per stream
                        if(isset($aStreamGames[$oChapter->game_id]))
                        {
                            $aStreamGames[$oChapter->game_id]->duration += $iChapterDuration;
                            $aStreamGames[$oChapter->game_id]->durationTime = $this->secondsToTime($aStreamGames[$oChapter->game_id]->duration, true);
                        }
                        else
                        {
                            $aStreamGames[$oChapter->game_id] = (object) [
                                'name' => $oChapter->TwitchGame->name,
                                'duration' => $iChapterDuration,
                                'img' => str_replace(['{width}', '{height}'], [150, 150], $oChapter->TwitchGame->box_art_url),
                                'durationTime' => $this->secondsToTime($iChapterDuration, true)
                            ];
                        }

                        // Calc total game duration
                        if(isset($aGames[$oChapter->game_id]))
                        {
                            $aGames[$oChapter->game_id]->duration += $iChapterDuration;
                            $aGames[$oChapter->game_id]->durationTime = $this->secondsToTime($aGames[$oChapter->game_id]->duration, true);;
                        }
                        else
                        {
                            $aGames[$oChapter->game_id] = (object) [
                                'name' => $oChapter->TwitchGame->name,
                                'duration' => $iChapterDuration,
                                'img' => str_replace(['{width}', '{height}'], [150, 150], $oChapter->TwitchGame->box_art_url),
                                'durationTime' => $this->secondsToTime($iChapterDuration, true)
                            ];
                        }
                    }
                }
                // Sort high->low duration
                if(!empty($aStreamGames))
                    $oStream->games = $this->sortDuration($aStreamGames);
            }
            // Sort high->low duration
            if(!empty($aGames))
                $oUser->games = $this->sortDuration($aGames);

            $oUser->totalDurationTime = $this->secondsToTime($iTotalStreamDuration, true);
        }

        return view('channel.index', [
            'user' => $oUser
        ]);
    }

    public function sortDuration($array)
    {
        usort($array, function($a, $b) {
            return $a->duration < $b->duration;
        });
        return $array;
    }

    public function getPercentage($iTotal, $iPart)
    {
        return number_format(($iPart / $iTotal * 100), 2, ',', '');
    }

    public function timeToString($oTime)
    {
        $a = [];
        $aTimeWords = [
            'd' => ['day', 'days'],
            'h' => ['hour', 'hours'],
            'm' => ['minute', 'minutes'],
            's' => ['second', 'seconds']
        ];

        foreach($oTime as $s => $v)
        {
            if($v > 0)
                $a[] = $v .' '. $aTimeWords[$s][($v == 1 ? 0 : 1)]; 
        }

        return trim(implode(', ', $a));
    }

    public function secondsToTime($inputSeconds, $bString = false)
    {
        $secondsInAMinute = 60;
        $secondsInAnHour  = 60 * $secondsInAMinute;
        $secondsInADay    = 24 * $secondsInAnHour;

        // extract days
        $days = floor($inputSeconds / $secondsInADay);

        // extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);

        // extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        // return the final array
        $o = (object) [
            'd' => (int) $days,
            'h' => (int) $hours,
            'm' => (int) $minutes,
            's' => (int) $seconds
        ];

        return $bString ? $this->timeToString($o) : $o;
    }

    public function secondsToVodTimeStamp($inputSeconds)
    {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;

        // extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($inputSeconds / $secondsInAnHour);

        // extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);

        // extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);

        // return the final array
        $a = [
            'h' => (int) $hours,
            'm' => (int) $minutes,
            's' => (int) $seconds
        ];

        $s = '';
        foreach($a as $k => $v)
        {
            if($v > 0)
                $s .= $v . $k;
        }
        return $s == '' ? false : $s;
    }
}