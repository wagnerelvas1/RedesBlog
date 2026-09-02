<?php

namespace Database\Seeders;

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;
use Database\Seeders\Support\ImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommunitySeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, title: string, description: string}>
     */
    private const COMMUNITIES = [
        ['name' => 'announcements', 'title' => 'Announcements', 'description' => 'Official news and platform updates.'],
        ['name' => 'webdev', 'title' => 'Web Development', 'description' => 'Everything about building for the web.'],
        ['name' => 'gaming', 'title' => 'Gaming', 'description' => 'Games, reviews and everything in between.'],
        ['name' => 'photography', 'title' => 'Photography', 'description' => 'Share your shots and get feedback.'],
        ['name' => 'askanything', 'title' => 'Ask Anything', 'description' => 'Open questions, honest answers.'],
        ['name' => 'laravel', 'title' => 'Laravel', 'description' => 'The PHP framework for web artisans.'],
        ['name' => 'funny', 'title' => 'Funny', 'description' => 'Things that made us laugh today.'],
        ['name' => 'news', 'title' => 'World News', 'description' => 'What is happening right now.'],
    ];

    public function run(): void
    {
        $users = User::query()->pluck('id');

        DB::transaction(function () use ($users): void {
            foreach (self::COMMUNITIES as $data) {
                $creatorId = (int) $users->random();

                $community = Community::query()->create([
                    ...$data,
                    'rules' => "1. Be respectful.\n2. Stay on topic.\n3. No spam or self-promotion.",
                    'avatar_path' => ImageGenerator::store('communities', 256, 256),
                    'banner_path' => ImageGenerator::store('communities', 1200, 240),
                    'created_by' => $creatorId,
                    'members_count' => 1,
                ]);

                $community->members()->attach($creatorId, [
                    'role' => CommunityRole::Admin->value,
                    'is_creator' => true,
                ]);
            }
        });
    }
}
