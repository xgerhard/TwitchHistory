<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('login', 'Auth\LoginController@redirectToProvider');
Route::get('login/callback', 'Auth\LoginController@handleProviderCallback');

Route::get('stats/{userId}', 'StatsController@index');

Route::get('app/auth', 'AppController@auth');
Route::get('app/webhooks', 'AppController@webhooks');

Route::get('webhook/test/{userId}', 'WebhookController@test');
Route::get('webhook/check', 'WebhookController@checkWebhooks');
Route::get('webhook/{method}/{userId}', 'WebhookController@challenge');
Route::post('webhook/{method}/{userId}', 'WebhookController@parse');