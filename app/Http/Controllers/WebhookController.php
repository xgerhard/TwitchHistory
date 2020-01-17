<?php

namespace App\Http\Controllers;

use App\Webhook;
use App\TwitchAPI;
use App\TwitchGame;
use App\TwitchStream;
use App\TwitchStreamChapter;

use Log;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;


class WebhookController extends Controller
{
    public function test($iUserId)
    {
        try
        {
            $oPayload = $this->getDemoData();
        }
        catch(Exception $e)
        {
            dd($e);
        }
    }

    public function create()
    {
        try
        {
            die;
            $oDate = Carbon::now();
            $oTwitchAPI = new TwitchAPI;

            $aIds = [41200623, 36769016, 71190292, 26490481, 54739364];

            if(!empty($aIds))
            {
                foreach($aIds as $iId)
                {
                    $oWebhook = Webhook::where('topic', 'https://twitchhistory.2g.be/webhook/streamchanged/'. $iId)->first;
                    if(!$oWebhook)
                    {
                        $oRegisterWebhook = $oTwitchAPI->webhook([
                            'hub.callback' => 'https://twitchhistory.2g.be/webhook/streamchanged/'. $iId,
                            'hub.mode' => 'subscribe',
                            'hub.topic' => 'https://api.twitch.tv/helix/streams?user_id='. $iId,
                            'hub.lease_seconds' => 864000,
                            'hub.secret' => 'historytwitch'
                        ]);

                        Webhook::create([
                            'topic' => 'https://api.twitch.tv/helix/streams?user_id='. $iId,
                            'lease_seconds' => 864000,
                            'secret' => 'historytwitch',
                            'expires_at' => $oDate->addSeconds(864000)
                        ]);
                    }
                }
            }
        }
        catch(Exception $e)
        {
            Log::error($e);
        }
    }

    public function challenge(Request $request, $strMethod, $iUserId)
    {
        if($request->has('hub_topic') && $request->has('hub_challenge'))
        {
            $strTopic = urldecode($request->get('hub_topic'));
            $oWebhook = Webhook::where('topic', $strTopic)->first();
            if($oWebhook)
            {
                // Verify webhook
                echo urldecode($request->get('hub_challenge'));
                Log::error('Verified webhook: '. $strTopic);
            }
        }
    }

    public function parse(Request $request, $strMethod, $iUserId)
    {
        $oPayload = json_decode(file_get_contents('php://input'));
        Log::error(print_r($oPayload, true));

        switch(strtolower($strMethod))
        {
            case 'streamchanged';
                $this->streamChanged($iUserId, $oPayload);
            break;
        }
    }

    private function streamChanged($iUserId, $oPayload)
    {
        if(isset($oPayload->data))
        {
            if(!empty($oPayload->data))
            {
                foreach($oPayload->data as $oEvent)
                {
                    if($oEvent->user_id == $iUserId)
                    {
                        $oStream = $this->addStream($oEvent->id, $iUserId, $oEvent->started_at, $oEvent->title);
                        if($oStream)
                        {
                            $bCreateChapter = false;
                            $oLastChapter = $oStream->TwitchStreamChapters->last();
                            if($oLastChapter)
                            {
                                // Last chapter ended
                                if($oLastChapter->duration > 0)
                                    $bCreateChapter = true;

                                // Game changed
                                elseif($oLastChapter->game_id != $oEvent->game_id)
                                {
                                    $oLastChapter = $this->endChapter($oLastChapter);
                                    $bCreateChapter = true;
                                    Log::error($iUserId .' game changed '. $oLastChapter->game_id .' ->' . $oEvent->game_id);
                                }
                            }
                            else
                                $bCreateChapter = true;

                            if($bCreateChapter)
                            {
                                // Store game
                                $this->addGame($oEvent->game_id);

                                // Create new chapter
                                $oStreamChapter = TwitchStreamChapter::create([
                                    'stream_id' => $oStream->id,
                                    'created_at' => ($oLastChapter ? Carbon::now() : Carbon::parse($oEvent->started_at)),
                                    'game_id' => $oEvent->game_id
                                ]);
                            }
                        }
                    }
                }
            }
            else
            {
                $oStream = TwitchStream::with('TwitchStreamChapters')->where('user_id', $iUserId)->first();
                if($oStream)
                {
                    $aChapters = $oStream->TwitchStreamChapters;
                    $iDuration = 0;
                    if($aChapters && !empty($aChapters))
                    {
                        foreach($aChapters as $oChapter)
                        {
                            if(!$oChapter->duration > 0)
                                $oChapter = $this->endChapter($oChapter);

                            $iDuration += $oChapter->duration;
                        }
                    }

                    if($iDuration > 0)
                    {
                        $oStream->duration = $iDuration;
                        $oStream->save();
                    }
                }

                // Stream went offline
                Log::error($iUserId .' stream ended');
            }
        }
        else
        {
            // Unexpected response
            Log::error('Unexpected webhook data: '. print_r($oPayload, true));
        }
    }

    private function endChapter($oChapter)
    {
        $dNow = Carbon::now();
        $oChapter->duration = $dNow->diffInSeconds($oChapter->created_at);
        $oChapter->updated_at = $dNow;
        $oChapter->save();
        return $oChapter;
    }

    private function addUser($iUserId, $strUsername)
    {
        $oUser = TwitchUser::find($iUserId);
        if(!$oUser)
        {
            $oUser = TwitchUser::create([
                'id' => $iUserId,
                'name' => $strUsername
            ]);
        }
        return $oUser;
    }

    private function addStream($iStreamId, $iUserId, $strStartDate, $strTitle = '')
    {
        $oStream = TwitchStream::with('TwitchStreamChapters')->find($iStreamId);
        if(!$oStream)
        {
            $oStream = TwitchStream::create([
                'id' => $iStreamId,
                'user_id' => $iUserId,
                'title' => $strTitle,
                'created_at' => Carbon::parse($strStartDate)
            ]);
            Log::error($iUserId .' stream started');
        }
        elseif($oStream->user_id != $iUserId)
            return false;

        return $oStream;
    }

    private function addGame($iGameId)
    {
        $oGame = TwitchGame::find($iGameId);
        if(!$oGame)
        {
            $oTwitchAPI = new TwitchAPI;
            $oGames = $oTwitchAPI->getGames([$iGameId]);
            if($oGames && isset($oGames->data) && !empty($oGames->data))
            {
                foreach($oGames->data as $oGameResult)
                {
                    if($oGameResult->id == $iGameId)
                    {
                        TwitchGame::create([
                            'id' => $oGameResult->id,
                            'name' => $oGameResult->name,
                            'box_art_url' => $oGameResult->box_art_url
                        ]);
                        break;
                    }
                }
            }
        }
    }

    private function getDemoData()
    {
        return (object) [
            'data' => [
                (object) [
                    'game_id' => 12344,
                    'id' => 479998225,
                    'language' => 'en',
                    'started_at' => '2020-01-16T07:59:49Z',
                    'tag_ids' => [
                        '6ea6bca4-4712-4ab9-a906-e3336a9d8039'
                    ],
                    'thumbnail_url' => 'https://static-cdn.jtvnw.net/previews-ttv/live_user_esamarathon-{width}x{height}.jpg',
                    'title' => 'sdfsdfsdfsdf',
                    'type' => 'live',
                    'user_id' => 54739364,
                    'user_name' => 'ESAMarathon',
                    'viewer_count' => 460
                ]
            ]
        ];
    }
}
