<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Voting surface shared by posts and comments.
 *
 * Writes always go through the vote service; this trait only exposes the
 * relation and the read-side helpers.
 */
trait HasVotes
{
    /**
     * @return MorphMany<Vote, $this>
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    /**
     * Adds a `viewer_vote` column holding -1, 0 or 1 for the given user.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithViewerVote(Builder $query, ?User $viewer): Builder
    {
        if ($viewer === null) {
            return $query->selectRaw('0 as viewer_vote');
        }

        return $query->addSelect([
            'viewer_vote' => Vote::query()
                ->selectRaw('COALESCE(value, 0)')
                ->whereColumn('votable_id', $this->getTable().'.id')
                ->where('votable_type', $this->getMorphClass())
                ->where('user_id', $viewer->id)
                ->limit(1),
        ]);
    }
}
