<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Socialite;
use Exception;

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
                echo 'y u deny';
            else
            {
                $oUser = Socialite::driver('twitch')->user();
                if(isset($oUser->name))
                    echo $oUser->name;
            }
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
    }
}