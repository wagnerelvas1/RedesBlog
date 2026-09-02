<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CommunityRepository;

/**
 * Editing is author-only; deleting is author or community admin.
 */
class CommentPolicy
{
    public function __construct(
        private readonly CommunityRepository $communities,
    ) {}

    public function view(?User $user, Comment $comment): bool
    {
        return true;
    }

    /**
     * Commenting requires an active membership of the post's community.
     */
    public function create(User $user, ?Post $post = null): bool
    {
        if ($post === null || $post->deleted_at !== null) {
            return false;
        }

        $community = $post->relationLoaded('community')
            ? $post->community
            : $post->community()->first();

        if (! $community instanceof Community) {
            return false;
        }

        $membership = $this->communities->membership($community, $user);

        return $membership !== null && ! $membership->isBanned();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id || $this->isCommunityAdmin($user, $comment);
    }

    public function vote(User $user, Comment $comment): bool
    {
        return $comment->deleted_at === null;
    }

    private function isCommunityAdmin(User $user, Comment $comment): bool
    {
        $post = $comment->relationLoaded('post') ? $comment->post : $comment->post()->first();

        if (! $post instanceof Post) {
            return false;
        }

        $community = $post->relationLoaded('community')
            ? $post->community
            : $post->community()->first();

        if (! $community instanceof Community) {
            return false;
        }

        return $this->communities->membership($community, $user)?->isAdmin() === true;
    }
}
