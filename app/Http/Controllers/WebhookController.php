<?php

namespace App\Http\Controllers;

use App\Webhook;
use App\TwitchAPI;
use App\StreamSession;
use App\TwitchGame;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Log;

class WebhookController extends Controller
{
    public function create()
    {
        try
        {
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
            $oLastStreamSession = StreamSession::where('user_id', $iUserId)->latest()->first();
            if(!empty($oPayload->data))
            {
                foreach($oPayload->data as $oEvent)
                {
                    if($oLastStreamSession)
                    {
                        if($oLastStreamSession->finished || $oLastStreamSession->game_id != $oEvent->game_id)
                        {
                            // Create new session
                            $oStreamSession = StreamSession::create([
                                'user_id' => $iUserId,
                                'started_at' => Carbon::parse($oEvent->started_at),
                                'game_id' => $oEvent->game_id,
                                'finished' => 0,
                                'stream_reference' => ($oLastStreamSession->finished ? 0 : ($oLastStreamSession->stream_reference == 0 ? $oLastStreamSession->id : $oLastStreamSession->stream_reference)),
                                'stream_id' => $oEvent->id
                            ]);

                            // If last session wasnt finished yet, stop it, and create a new session
                            if(!$oLastStreamSession->finished)
                            {
                                Log::error($iUserId .' game changed '. $oLastStreamSession->game_id .' ->' . $oEvent->game_id);
                                // Stop last session
                                $oLastStreamSession->finished = 1;
                                $oLastStreamSession->save();
                            }
                            else
                                Log::error($iUserId .' stream started');
                        }
                    }
                    else
                    {
                        // First time user
                        $oStreamSession = StreamSession::create([
                            'user_id' => $iUserId,
                            'started_at' => Carbon::parse($oEvent->started_at),
                            'game_id' => $oEvent->game_id,
                            'finished' => 0,
                            'stream_reference' => 0,
                            'stream_id' => $oEvent->id
                        ]);
                        Log::error($iUserId .' first time user - (save as started stream)');
                    }

                    // Check/store game
                    $oGame = TwitchGame::find($oEvent->game_id);
                    if(!$oGame)
                    {
                        $oTwitchAPI = new TwitchAPI;
                        $oGames = $oTwitchAPI->getGames([$oEvent->game_id]);
                        if($oGames && isset($oGames->data) && !empty($oGames->data))
                        {
                            foreach($oGames->data as $oGameResult)
                            {
                                if($oGameResult->id == $oEvent->game_id)
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
            }
            else
            {
                // Stream went offline
                if($oLastStreamSession && !$oLastStreamSession->finished)
                {
                    // Stop last session
                    $oLastStreamSession->finished = 1;
                    $oLastStreamSession->save();
                }
                Log::error($iUserId .' stream ended');
            }
        }
        else
        {
            // Unexpected response
            Log::error('Unexpected webhook data: '. print_r($oPayload, true));
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
