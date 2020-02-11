<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TwitchStreamChapterTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('twitch_stream_chapters')->insert([[
            'stream_id' => 111111111,
            'game_id' => 497057,
            'duration' => 1337,
            'created_at' => '2020-02-11 12:00:00',
            'updated_at' => '2020-02-11 12:22:17'
        ], [
            'stream_id' => 111111111,
            'game_id' => 459931,
            'duration' => 1000,
            'created_at' => '2020-02-11 12:22:17',
            'updated_at' => '2020-02-11 12:38:57'
        ]]);
    }
}
