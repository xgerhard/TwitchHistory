<?php

namespace App\Http\Controllers;

use App\TwitchAPI;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Log;

class AppController extends Controller
{
    public function webhooks()
    {
        $oTwitchAPI = new TwitchAPI;
        $oWebhooks = $oTwitchAPI->getWebhookSubscriptions();
        echo '<pre>';
        print_r($oWebhooks);
    }

    public function auth()
    {
        $oClient = new Client([
            'http_errors' => false, 
            'verify' => false
        ]);

        $oResponse = $oClient->request('POST', 'https://id.twitch.tv/oauth2/token', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $_ENV['TWITCH_CLIENT_ID'],
                'client_secret' => $_ENV['TWITCH_CLIENT_SECRET']
            ]
        ]);

        $oResponse = json_decode($oResponse->getBody()->getContents());
        Log::error(print_r($oResponse, true));
    }
}