<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;
use App\Repositories\CommunityRepository;

/**
 * Authorization for community management.
 *
 * The permission matrix lives in `.plan/app-inicial.md` §5: admins edit
 * settings and manage members, only the creator manages admins and deletes.
 */
class CommunityPolicy
{
    public function __construct(
        private readonly CommunityRepository $communities,
    ) {}

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Community $community): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Community $community): bool
    {
        return $this->communities->membership($community, $user)?->isAdmin() === true;
    }

    public function updateSettings(User $user, Community $community): bool
    {
        return $this->update($user, $community);
    }

    public function manageMembers(User $user, Community $community): bool
    {
        return $this->update($user, $community);
    }

    public function manageAdmins(User $user, Community $community): bool
    {
        return $this->isCreator($user, $community);
    }

    public function delete(User $user, Community $community): bool
    {
        return $this->isCreator($user, $community);
    }

    public function join(User $user, Community $community): bool
    {
        $membership = $this->communities->membership($community, $user);

        return $membership === null;
    }

    public function leave(User $user, Community $community): bool
    {
        $membership = $this->communities->membership($community, $user);

        return $membership !== null && ! $membership->is_creator;
    }

    /**
     * Posting requires an active, non-banned membership.
     */
    public function post(User $user, Community $community): bool
    {
        $membership = $this->communities->membership($community, $user);

        return $membership !== null && ! $membership->isBanned();
    }

    private function isCreator(User $user, Community $community): bool
    {
        return $this->communities->membership($community, $user)?->is_creator === true;
    }
}
