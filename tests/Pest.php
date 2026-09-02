<?php

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Creates a community whose creator is `$creator` (or a fresh user), with the
 * pivot row that makes them the immovable creator/admin.
 */
function communityOwnedBy(?User $creator = null, array $attributes = []): Community
{
    $creator ??= User::factory()->create();

    return Community::factory()->createdBy($creator)->create($attributes);
}

/**
 * Attaches a user to a community with the given role.
 */
function joinCommunity(Community $community, User $user, CommunityRole $role = CommunityRole::Member): User
{
    $community->members()->attach($user->id, [
        'role' => $role->value,
        'is_creator' => false,
    ]);

    $community->members_count = $community->members()->wherePivotNull('banned_at')->count();
    $community->save();

    return $user;
}

/**
 * Attaches a banned member.
 */
function banFromCommunity(Community $community, User $user): User
{
    $community->members()->syncWithoutDetaching([
        $user->id => [
            'role' => CommunityRole::Member->value,
            'is_creator' => false,
            'banned_at' => now(),
        ],
    ]);

    return $user;
}
