<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CommunityRepository;

/**
 * Editing is author-only; deleting is author or community admin; pinning is
 * admin-only. Saving needs no community membership at all.
 */
class PostPolicy
{
    public function __construct(
        private readonly CommunityRepository $communities,
    ) {}

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        return true;
    }

    /**
     * Posting requires an active, non-banned membership of the target
     * community, which is passed alongside the class name.
     */
    public function create(User $user, ?Community $community = null): bool
    {
        if ($community === null) {
            return false;
        }

        $membership = $this->communities->membership($community, $user);

        return $membership !== null && ! $membership->isBanned();
    }

    public function update(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $post->user_id === $user->id || $this->isCommunityAdmin($user, $post);
    }

    public function restore(User $user, Post $post): bool
    {
        return $this->isCommunityAdmin($user, $post);
    }

    public function pin(User $user, Post $post): bool
    {
        return $this->isCommunityAdmin($user, $post);
    }

    public function save(User $user, Post $post): bool
    {
        return true;
    }

    public function vote(User $user, Post $post): bool
    {
        return $post->deleted_at === null;
    }

    private function isCommunityAdmin(User $user, Post $post): bool
    {
        $community = $post->relationLoaded('community')
            ? $post->community
            : $post->community()->first();

        if (! $community instanceof Community) {
            return false;
        }

        return $this->communities->membership($community, $user)?->isAdmin() === true;
    }
}
