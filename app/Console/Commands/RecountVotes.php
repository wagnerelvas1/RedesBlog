<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Post;
use App\Services\VoteService;
use Illuminate\Console\Command;

/**
 * Rebuilds every cached vote counter and rank from the `votes` table.
 *
 * Useful after seeding and as a reconciliation step if counters ever drift.
 */
class RecountVotes extends Command
{
    protected $signature = 'votes:recount';

    protected $description = 'Recompute score, vote counts and ranks for every post and comment';

    public function handle(VoteService $votes): int
    {
        $posts = 0;
        $comments = 0;

        Post::query()->withTrashed()->chunkById(200, function ($chunk) use ($votes, &$posts): void {
            foreach ($chunk as $post) {
                $votes->recount($post);
                $posts++;
            }
        });

        Comment::query()->withTrashed()->chunkById(200, function ($chunk) use ($votes, &$comments): void {
            foreach ($chunk as $comment) {
                $votes->recount($comment);
                $comments++;
            }
        });

        $this->info("Recounted {$posts} posts and {$comments} comments.");

        return self::SUCCESS;
    }
}
