<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Log;
use Socialite;
use Exception;
use App\TwitchUser;
use App\TwitchWebhookHandler;

class LoginController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('twitch')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Request $request)
    {
        try
        {
            if(!$request->input('code'))
            {
                // This is not really nice, but works for now, on deny just send em back
                return Socialite::driver('twitch')->redirect();
            }
            else
            {
                $oUser = Socialite::driver('twitch')->user();
                if(isset($oUser->id))
                {
                    $oTwitchUser = TwitchUser::find($oUser->id);
                    if(!$oTwitchUser)
                    {
                        $oTwitchUser = TwitchUser::create([
                            'id' => $oUser->id,
                            'name' => $oUser->name
                        ]);
                    }

                    // Check if webhook exists and renew if expired
                    $oWebhookHandler = new TwitchWebhookHandler();
                    if(!$oWebhookHandler->webhookExists($oWebhookHandler->getStreamChangedTopicUrl($oUser->id)))
                    {
                        $oNewWebhook = $oWebhookHandler->createWebhook(
                            $oWebhookHandler->getStreamChangedTopicUrl($oUser->id),
                            $oWebhookHandler->getStreamChangedCallbackUrl($oUser->id)
                        );
                    }
                    return redirect('stats/'. $oUser->id);
                }
            }
        }
        catch(Exception $e)
        {
            Log::error($e);
            return $e->getMessage();
        }
    }
}