<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TwitchStreamTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('twitch_streams')->insert([
            'id' => 111111111,
            'title' => 'one does not simply stream',
            'created_at' => '2020-02-11 12:00:00',
            'updated_at' => '2020-02-11 12:38:57',
            'user_id' => 49056910,
            'vod_id' => 222222222,
            'duration' => 2337
        ]);
    }
}
