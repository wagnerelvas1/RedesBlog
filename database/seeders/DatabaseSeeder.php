<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Builds a browsable application: users, communities with members,
     * posts with images, threaded comments and votes.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CommunitySeeder::class,
            MembershipSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            VoteSeeder::class,
        ]);

        // Rebuild score/counters/ranks so the Hot, Top and Best sorts are real.
        Artisan::call('votes:recount');

        $this->command->info(trim(Artisan::output()));
    }
}
