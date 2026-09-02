<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Model;

/**
 * Persistence for individual vote rows.
 */
final class VoteRepository
{
    public function find(User $user, Model $votable): ?Vote
    {
        return Vote::query()
            ->where('user_id', $user->id)
            ->where('votable_type', $votable->getMorphClass())
            ->where('votable_id', $votable->getKey())
            ->first();
    }

    /**
     * The owner columns are set explicitly: only `value` is mass assignable on
     * the model, so `updateOrCreate` would silently drop them.
     */
    public function upsert(User $user, Model $votable, int $value): Vote
    {
        $vote = $this->find($user, $votable);

        if ($vote === null) {
            $vote = new Vote;
            $vote->user_id = $user->id;
            $vote->votable_type = $votable->getMorphClass();
            $vote->votable_id = (int) $votable->getKey();
        }

        $vote->value = $value;
        $vote->save();

        return $vote;
    }

    public function delete(User $user, Model $votable): void
    {
        Vote::query()
            ->where('user_id', $user->id)
            ->where('votable_type', $votable->getMorphClass())
            ->where('votable_id', $votable->getKey())
            ->delete();
    }

    /**
     * Authoritative tallies straight from the `votes` table.
     *
     * @return array{up: int, down: int}
     */
    public function tallies(Model $votable): array
    {
        $rows = Vote::query()
            ->selectRaw('value, COUNT(*) as total')
            ->where('votable_type', $votable->getMorphClass())
            ->where('votable_id', $votable->getKey())
            ->groupBy('value')
            ->pluck('total', 'value');

        return [
            'up' => (int) ($rows[1] ?? 0),
            'down' => (int) ($rows[-1] ?? 0),
        ];
    }
}
