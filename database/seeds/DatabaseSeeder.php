<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(TwitchUserTableSeeder::class);
        $this->call(TwitchGameTableSeeder::class);
        $this->call(TwitchStreamTableSeeder::class);
        $this->call(TwitchStreamChapterTableSeeder::class);
    }
}
