<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VoteSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::query()->pluck('id');

        Post::query()->chunkById(100, function (EloquentCollection $posts) use ($userIds): void {
            $this->voteOn($posts, $userIds, 1, 25);
        });

        Comment::query()->chunkById(200, function (EloquentCollection $comments) use ($userIds): void {
            $this->voteOn($comments, $userIds, 0, 12);
        });
    }

    /**
     * Inserts a batch of unique votes, skewed positive like a real feed.
     *
     * @param  EloquentCollection<int, covariant \Illuminate\Database\Eloquent\Model>  $votables
     * @param  Collection<int, int>  $userIds
     */
    private function voteOn(EloquentCollection $votables, Collection $userIds, int $min, int $max): void
    {
        $rows = [];
        $now = now();

        foreach ($votables as $votable) {
            $voters = $userIds->shuffle()->take(random_int($min, $max));

            foreach ($voters as $userId) {
                $rows[] = [
                    'user_id' => $userId,
                    'votable_type' => $votable->getMorphClass(),
                    'votable_id' => $votable->getKey(),
                    // Skewed ~75% positive.
                    'value' => random_int(1, 100) <= 75 ? 1 : -1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('votes')->insertOrIgnore($rows);
        }
    }
}
