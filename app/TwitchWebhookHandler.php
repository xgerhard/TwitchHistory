<?php

namespace App;

use App\Webhook;
use App\TwitchAPI;

use Log;
use Exception;
use Carbon\Carbon;

class TwitchWebhookHandler
{
    public function webhookExists($strSearch, $bExpireCheck = true)
    {
        $oWebhook = Webhook::where('topic', $strSearch)->orWhere('callback', $strSearch)->first();
        if($oWebhook)
        {
            if($bExpireCheck)
            {
                $oDate = Carbon::now();
                if($oDate->gte($oWebhook->expires_at))
                    return false;
            }
            return true;
        }
        return false;
    }

    public function createWebhook($strTopic, $strCallback, $strMode = 'subscribe', $iLeaseSeconds = 864000)
    {
        try
        {
            $strMode = strtolower($strMode);
            if($strMode != 'subscribe')
                $strMode = 'unsubscribe';

            $oDate = Carbon::now();
            $oDate->addSeconds($iLeaseSeconds);
            $strSecret = uniqid();

            $oWebhook = Webhook::where('topic', $strTopic)->first();
            if(!$oWebhook)
            {
                if($strMode == 'subscribe')
                {
                    // Store webhook
                    $oWebhook = Webhook::create([
                        'topic' => $strTopic,
                        'lease_seconds' => $iLeaseSeconds,
                        'secret' => $strSecret,
                        'expires_at' => $oDate,
                        'callback' => $strCallback
                    ]);
                }
            }
            else
            {
                if($strMode == 'subscribe')
                {
                    // Update webhook
                    $oWebhook->lease_seconds = $iLeaseSeconds;
                    $oWebhook->secret = $strSecret;
                    $oWebhook->expires_at = $oDate;
                    $oWebhook->save();
                }
                else
                    $oWebhook->forceDelete();
            }

            $oTwitchAPI = new TwitchAPI;
            $oRegisterWebhook = $oTwitchAPI->webhook([
                'hub.callback' => $strCallback,
                'hub.mode' => $strMode,
                'hub.topic' => $strTopic,
                'hub.lease_seconds' => $iLeaseSeconds,
                'hub.secret' => $strSecret
            ]);

            if($oRegisterWebhook)
            {
                Log::info('[Webhook create] Webhook '. $strTopic .  ' succesfully '. ($strMode == 'subscribe' ? 'added' : 'removed'));
                return true;
            }
            return false;
        }
        catch(Exception $e)
        {
            Log::error($e->getMessage());
        }
    }

    public function getStreamChangedCallbackUrl($iUserId)
    {
        return 'https://twitchhistory.2g.be/webhook/streamchanged/'. $iUserId;
    }

    public function getStreamChangedTopicUrl($iUserId)
    {
        return 'https://api.twitch.tv/helix/streams?user_id='. $iUserId;
    }
}