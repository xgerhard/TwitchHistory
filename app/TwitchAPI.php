<?php

namespace App;

use Exception;
use GuzzleHttp\Client;
use Log;

class TwitchAPI
{
    private $baseUrl = 'https://api.twitch.tv/helix/';
    private $v5 = 'application/vnd.twitchtv.v5+json';

    public function getGames($aGames = [])
    {
        $a = [];
        foreach($aGames as $iGameId)
            $a[] = 'id='. $iGameId;

        return $this->request(
            'games?'. implode('&', $a)
        );
    }

    public function webhook($aData = [])
    {
        $webhook = $this->request(
            'webhooks/hub',
            false,
            [],
            'POST',
            $aData
        );

        return $webhook === null ? true : false;
    }

    /**
     * Get users follows
     *
     * @param string $strFromId User Id, response is information about users who are being followed by this user
     * @param string $strToId User Id, response is information about users who are following this user
     * @param string $strAfter Cursor for forward pagination
     * @param int $iFirst Maximum number of objects to return
     *
     * @return json Twitch data response
     */
    public function getUsersFollows($strFromId = null, $strToId = null, $strAfter = null, $iFirst = null)
    {
        if(!$strFromId && !$strToId)
            throw new Exception('At minimum, from_id or to_id has to be provided');

        $a = [];
        if($strFromId)
            $a['from_id'] = $strFromId;
        if($strToId)
            $a['to_id'] = $strToId;
        if($strAfter)
            $a['after'] = $strAfter;
        if($iFirst)
            $a['first'] = $iFirst;

        // Move this later, since the API is getting slammed with rate limit errors, lets quick fix this..
        $aRequestHeaders = [];
        $oOAuthHandler = new OAuthHandler('twitch');
        $oOAuthSession = $oOAuthHandler->isAuthValid(null, true);

        if($oOAuthSession)
            $aRequestHeaders['Authorization'] = 'Bearer '. $oOAuthSession->access_token;

        return $this->request(
            'users/follows?' . http_build_query($a),
            false,
            $aRequestHeaders
        );
    }

    /**
     * Search users
     *
     * @param array $aSearchUsers Array of usernames to search for
     *
     * @return json Twitch data response
     */
    public function searchUsers($aSearchUsers)
    {
        return $this->request(
            'https://api.twitch.tv/kraken/users?login='. urlencode(implode(',', $aSearchUsers)),
            true,
            ['Accept' => $this->v5]
        );
    }

    /**
     * Get chatters / viewerlist
     *
     * @param string $strChannel Channel name
     *
     * @return json Twitch data response
     */
    public function getChatters($strChannel)
    {
        return $this->request(
            'https://tmi.twitch.tv/group/user/'. strtolower($strChannel) .'/chatters',
            true
        );
    }

    /**
     * Fire requests with Guzzle
     *
     * @param string $strEndpoint Url or endpoint to retrieve
     * @param boolean $bFullUrl True for for url, false for helix endpoint
     * @param array $aHeaders Optional request headers
     *
     * @return json Twitch data response
     */
    private function request($strEndpoint, $bFullUrl = false, $aHeaders = [], $strMethod = 'GET', $aPayload = [])
    {
        $aRequestHeaders = [
            'User-Agent' => '2g.be - xgerhard@2g.be',
            'Client-ID' => ENV('TWITCH_CLIENT_ID'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        if(!empty($aHeaders))
            $aRequestHeaders = array_merge($aRequestHeaders, $aHeaders);

        $aSetup = [
            'headers' => $aRequestHeaders
        ];

        if(($strMethod == 'PUT' || $strMethod == 'POST' || $strMethod == 'PATCH') && !empty($aPayload))
            $aSetup['json'] = $aPayload;

        $oGuzzle = new Client([
            //'http_errors' => false, 
            'verify' => false
        ]);

        try
        {
            $res = $oGuzzle->request(
                $strMethod,
                $bFullUrl ? $strEndpoint : $this->baseUrl . $strEndpoint,
                $aSetup
            );

            if(in_array($res->getStatusCode(), [200, 202]))
            {
                if(isset($res->getHeader('Ratelimit-Remaining')[0]) && $res->getHeader('Ratelimit-Remaining')[0] == 0)
                {
                    Log::error('Twitch rate limit reached');
                    throw new Exception('please try again later');
                }

                return json_decode($res->getBody());
            }
            else
            {
                throw new Exception('Unexpected Twitch response ('. $res->getStatusCode() .')');
            }
        }
        catch(\GuzzleHttp\Exception\ClientException $e)
        {
            throw new Exception($e);
            Log::error($e);
            throw new Exception('Twitch error, please try again later');            
        }
    }
}
?>