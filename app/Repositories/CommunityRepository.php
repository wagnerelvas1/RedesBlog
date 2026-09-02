<?php

namespace App\Repositories;

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Queries and persistence for communities and their membership pivot.
 */
final class CommunityRepository
{
    /**
     * Per-request membership cache, so policies resolving the same pair
     * repeatedly do not re-query.
     *
     * @var array<string, CommunityMember|null>
     */
    private array $membershipCache = [];

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, User $creator): Community
    {
        $community = new Community($attributes);
        $community->name = $attributes['name'];
        $community->creator()->associate($creator);
        $community->members_count = 0;
        $community->save();

        return $community;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Community $community, array $attributes): Community
    {
        $community->fill($attributes);
        $community->save();

        return $community;
    }

    /**
     * @param  array{search?: string|null, sort?: string|null, filter?: string|null}  $filters
     * @return LengthAwarePaginator<int, Community>
     */
    public function paginateForIndex(array $filters, ?User $viewer): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? 'members';

        return Community::query()
            ->when($search, fn ($query, string $term) => $query->where(
                fn ($inner) => $inner
                    ->where('name', 'ilike', '%'.$term.'%')
                    ->orWhere('title', 'ilike', '%'.$term.'%')
            ))
            ->when(
                ($filters['filter'] ?? null) === 'joined' && $viewer !== null,
                fn ($query) => $query->whereHas(
                    'members',
                    fn ($inner) => $inner
                        ->where('users.id', $viewer?->id)
                        ->whereNull('community_user.banned_at')
                ),
            )
            ->when($viewer !== null, fn ($query) => $query->withExists([
                'members as is_member' => fn ($inner) => $inner
                    ->where('users.id', $viewer?->id)
                    ->whereNull('community_user.banned_at'),
            ]))
            ->when($sort === 'new', fn ($query) => $query->latest('id'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when(
                ! in_array($sort, ['new', 'name'], true),
                fn ($query) => $query->orderByDesc('members_count')->orderBy('id'),
            )
            ->paginate(24)
            ->withQueryString();
    }

    /**
     * Communities the user belongs to, for the sidebar nav.
     *
     * @return Collection<int, Community>
     */
    public function forSidebar(User $user): Collection
    {
        return Community::query()
            ->whereHas('members', fn ($query) => $query
                ->where('users.id', $user->id)
                ->whereNull('community_user.banned_at'))
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    /**
     * Membership row for a user in a community, including banned rows.
     */
    public function membership(Community $community, ?User $user): ?CommunityMember
    {
        if ($user === null) {
            return null;
        }

        $key = $community->id.':'.$user->id;

        return $this->membershipCache[$key] ??= CommunityMember::query()
            ->where('community_id', $community->id)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * @param  array{search?: string|null, role?: string|null}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function members(Community $community, array $filters): LengthAwarePaginator
    {
        $relation = $community->members()
            ->orderByPivot('is_creator', 'desc')
            ->orderByPivot('role')
            ->orderBy('users.username');

        $search = $filters['search'] ?? null;
        $role = $filters['role'] ?? null;

        if ($search !== null && $search !== '') {
            $relation->where(fn ($inner) => $inner
                ->where('users.username', 'ilike', '%'.$search.'%')
                ->orWhere('users.name', 'ilike', '%'.$search.'%'));
        }

        if ($role !== null && $role !== '') {
            $relation->wherePivot('role', $role);
        }

        /** @var LengthAwarePaginator<int, User> $paginator */
        $paginator = $relation->paginate(30)->withQueryString();

        return $paginator;
    }

    public function attachMember(
        Community $community,
        User $user,
        CommunityRole $role = CommunityRole::Member,
        bool $isCreator = false,
    ): void {
        $community->members()->attach($user->id, [
            'role' => $role->value,
            'is_creator' => $isCreator,
        ]);

        $this->forget($community, $user);
    }

    /**
     * @param  array<string, mixed>  $pivot
     */
    public function updateMember(Community $community, User $user, array $pivot): void
    {
        $community->members()->updateExistingPivot($user->id, $pivot);

        $this->forget($community, $user);
    }

    public function detachMember(Community $community, User $user): void
    {
        $community->members()->detach($user->id);

        $this->forget($community, $user);
    }

    /**
     * Recomputes `members_count` from the pivot, ignoring banned rows.
     */
    public function syncMembersCount(Community $community): void
    {
        $community->members_count = $community->members()
            ->wherePivotNull('banned_at')
            ->count();

        $community->save();
    }

    private function forget(Community $community, User $user): void
    {
        unset($this->membershipCache[$community->id.':'.$user->id]);
    }
}
