<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TwitchGameTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('twitch_games')->insert([[
            'id' => 497057,
            'name' => 'Destiny 2',
            'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/Destiny%202-{width}x{height}.jpg'
        ], [
            'id' => 459931,
            'name' => 'Old School RuneScape',
            'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/Old%20School%20RuneScape-{width}x{height}.jpg'
        ]]);
    }
}
