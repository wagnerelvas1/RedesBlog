<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\VoteRepository;
use App\Support\Ranking;
use Illuminate\Support\Facades\DB;

/**
 * Casting and clearing votes.
 *
 * Counters are recomputed with a COUNT rather than incremented, so they can
 * never drift out of step with the `votes` table.
 */
final class VoteService
{
    public function __construct(
        private readonly VoteRepository $votes,
    ) {}

    public function cast(User $user, Post|Comment $votable, int $value): VoteResult
    {
        return DB::transaction(function () use ($user, $votable, $value): VoteResult {
            $locked = $this->lock($votable);

            $this->votes->upsert($user, $locked, $value);

            return $this->recount($locked, $value);
        });
    }

    public function clear(User $user, Post|Comment $votable): VoteResult
    {
        return DB::transaction(function () use ($user, $votable): VoteResult {
            $locked = $this->lock($votable);

            $this->votes->delete($user, $locked);

            return $this->recount($locked, 0);
        });
    }

    /**
     * Rebuilds the cached counters and ranks of one item from its vote rows.
     */
    public function recount(Post|Comment $votable, int $viewerVote = 0): VoteResult
    {
        $tallies = $this->votes->tallies($votable);
        $score = $tallies['up'] - $tallies['down'];

        $votable->upvotes_count = $tallies['up'];
        $votable->downvotes_count = $tallies['down'];
        $votable->score = $score;

        if ($votable instanceof Post) {
            $votable->hot_rank = Ranking::hot($score, $votable->created_at);
        } else {
            $votable->best_rank = Ranking::best($tallies['up'], $tallies['down']);
        }

        $votable->save();

        return new VoteResult($score, $tallies['up'], $tallies['down'], $viewerVote);
    }

    /**
     * Row lock so concurrent votes on the same item serialise.
     */
    private function lock(Post|Comment $votable): Post|Comment
    {
        /** @var Post|Comment|null $locked */
        $locked = $votable->newQuery()
            ->withTrashed()
            ->lockForUpdate()
            ->find($votable->getKey());

        return $locked ?? $votable;
    }
}
