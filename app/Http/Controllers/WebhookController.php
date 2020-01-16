<?php

namespace App\Http\Controllers;

use App\Webhook;
use App\TwitchAPI;
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

            $oRegisterWebhook = $oTwitchAPI->webhook([
                'hub.callback' => 'https://twitchhistory.2g.be/webhook/streamchanged',
                'hub.mode' => 'subscribe',
                'hub.topic' => 'https://api.twitch.tv/helix/streams?user_id=41200623',
                'hub.lease_seconds' => 864000,
                'hub.secret' => 'historytwitch'
            ]);

            Webhook::create([
                'topic' => 'https://api.twitch.tv/helix/streams?user_id=41200623',
                'lease_seconds' => 864000,
                'secret' => 'historytwitch',
                'expires_at' => $oDate->addSeconds(864000)
            ]);
        }
        catch(Exception $e)
        {
            Log::error($e);
        }
    }

    public function challenge(Request $request, $strMethod)
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

    public function parse(Request $request, $strMethod)
    {
        $oPayload = json_decode(file_get_contents('php://input'));
        Log::error(print_r($oPayload, true));
    }
}
