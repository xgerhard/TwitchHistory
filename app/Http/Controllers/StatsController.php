<?php

namespace App\Http\Controllers;

use App\TwitchStream;
use App\TwitchAPI;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;

class StatsController extends Controller
{
    public function index(Request $request, $iUserId)
    {
        $dNow = Carbon::now();

        $oStreams = TwitchStream::where('user_id', $iUserId)->orderBy('created_at', 'desc')->with('TwitchStreamChapters')->get();
        if($oStreams->count() > 0)
        {
            foreach($oStreams as $oStream)
            {
                $dStreamStart = Carbon::parse($oStream->created_at);
                $iStreamDuration = $oStream->duration == 0 ? $dNow->diffInSeconds($dStreamStart) : $oStream->duration;

                echo '<h3>'. $dStreamStart->format('d-m-Y') .' '. $oStream->title . ($oStream->duration == 0 ? ' [Live]' : '') .'</h3>';
                echo '<p>'. ($oStream->duration == 0 ? 'Stream uptime' : 'Streamed for'). ': '. $this->secondsToTime($iStreamDuration, true) .'</p>';

                $oChapters = $oStream->TwitchStreamChapters;
                $aGames = [];

                if($oChapters && $oChapters->count() > 0)
                {
                    echo 'Chapters:<ul>';
                    foreach($oChapters as $oChapter)
                    {
                        $dStart = Carbon::parse($oChapter->created_at);
                        $iChapterDuration = $oChapter->duration == 0 ? $dNow->diffInSeconds($dStart) : $oChapter->duration;

                        echo '<li>'. $oChapter->TwitchGame->name . ' ('.  $this->secondsToTime($iChapterDuration, true) .')'. ($oChapter->duration == 0 ? ' [Currently playing]' : '') . '</li>';

                        if(isset($aGames[$oChapter->game_id]))
                        {
                            $aGames[$oChapter->game_id]->duration += $iChapterDuration;
                        }
                        else
                        {
                            $aGames[$oChapter->game_id] = (object) [
                                'name' => $oChapter->TwitchGame->name,
                                'duration' => $iChapterDuration
                            ];
                        }
                    }
                    echo '</ul>';

                    if(!empty($aGames))
                    {
                        usort($aGames, function($a, $b) {
                            return $a->duration < $b->duration;
                        });

                        echo 'Games:<ul>';
                        foreach($aGames as $oGame)
                        {

                            echo '<li>'. $oGame->name . ' ('. $this->secondsToTime($oGame->duration, true) .') ('. $this->getPercentage($iStreamDuration, $oGame->duration) .'%)</li>';
                        }
                        echo '</ul>';
                    }
                }
                echo '<hr>';
            }
        }
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
}