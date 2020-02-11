<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TwitchUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('twitch_users')->insert([
            'id' => 49056910,
            'name' => 'xgerhard'
        ]);
    }
}
