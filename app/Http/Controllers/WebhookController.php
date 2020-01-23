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
                    $oWebhook = Webhook::where('topic', 'https://twitchhistory.2g.be/webhook/streamchanged/'. $iId)->first();
                    if(!$oWebhook)
                    {
                        $strSecret = uniqid();
                        $oRegisterWebhook = $oTwitchAPI->webhook([
                            'hub.callback' => 'https://twitchhistory.2g.be/webhook/streamchanged/'. $iId,
                            'hub.mode' => 'subscribe',
                            'hub.topic' => 'https://api.twitch.tv/helix/streams?user_id='. $iId,
                            'hub.lease_seconds' => 864000,
                            'hub.secret' => $strSecret
                        ]);

                        Webhook::create([
                            'topic' => 'https://api.twitch.tv/helix/streams?user_id='. $iId,
                            'lease_seconds' => 864000,
                            'secret' => $strSecret,
                            'expires_at' => $oDate->addSeconds(864000)
                        ]);
                        Log::error('Webhook created: '. $iId);
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
        $body = file_get_contents('php://input');

        if($request->headers->has('X-Hub-Signature'))
        {
            $oWebhook = Webhook::where('topic', 'https://api.twitch.tv/helix/streams?user_id='. $iUserId)->first();
            if($oWebhook)
            {
                $strOurHmac = hash_hmac('sha256', $body, $oWebhook->secret);
                $aSignature = explode('=', $request->header('X-Hub-Signature'));
                $strTheirHmac = end($aSignature);

                if($strOurHmac !== $strTheirHmac)
                {
                    Log::error('Verify webhook '. print_r([
                        'title' => 'Refused webhook, HMAC mismatch',
                        'id' => $iUserId,
                        'our' => $strOurHmac,
                        'their' => $strTheirHmac
                    ], true));

                    return;
                }
            }
        }

        Log::error('Webhook user: '. $iUserId);
        Log::error(print_r(json_decode($body), true));

        switch(strtolower($strMethod))
        {
            case 'streamchanged';
                $this->streamChanged($iUserId, json_decode($body));
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

                            // Get the vod Id if it hasn't been set yet
                            if(!$oStream->vod_id)
                            {
                                $iVodId = $this->getVodId($iUserId, $oStream->id);
                                if($iVodId)
                                {
                                    $oStream->vod_id = $iVodId;
                                    $oStream->save();
                                }
                            }
                        }
                    }
                }
            }
            else
            {
                $oStream = TwitchStream::with('TwitchStreamChapters')
                    ->where('user_id', $iUserId)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if($oStream)
                {
                    Log::error($iUserId .' - '. $oStream->id .' - stream end debug - Stream found');
                    if($oStream->duration > 0)
                        Log::error($iUserId .' - '. $oStream->id .' - stream ended but with duration. Did the stream restart? ('. $oStream->duration .')');

                    $aChapters = $oStream->TwitchStreamChapters;
                    $iDuration = 0;
                    if($aChapters && !empty($aChapters))
                    {
                        Log::error($iUserId .' - '. $oStream->id .' - stream end debug - Chapters found');
                        foreach($aChapters as $oChapter)
                        {
                            if(!$oChapter->duration > 0)
                                $oChapter = $this->endChapter($oChapter);

                            Log::error($iUserId .' - '. $oStream->id .' - stream end debug - Chapter '. $oChapter->id .' - '. $oChapter->duration);

                            $iDuration += $oChapter->duration;
                        }
                    }
                    else
                    {
                        Log::error($iUserId .' - '. $oStream->id .' - stream end debug - No chapters found');
                    }

                    if($iDuration > 0)
                    {
                        $oStream->duration = $iDuration;
                        $oStream->save();
                    }
                }
                else
                {
                    Log::error($iUserId .' - stream end debug - No stream found');
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

    private function getVodId($iUserId, $iStreamId)
    {
        $oTwitchAPI = new TwitchAPI;
        $oVideos = $oTwitchAPI->getVideos([
            'user_id' => $iUserId,
            'type' => 'archive',
            'first' => 10
        ]);

        if($oVideos && isset($oVideos->data) && !empty($oVideos->data))
        {
            foreach($oVideos->data as $oVideo)
            {
                if(trim($oVideo->thumbnail_url) == '' || strpos($oVideo->thumbnail_url, '_'. $iStreamId .'_') !== false)
                    return $oVideo->id;
            }
        }
        return false;
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
