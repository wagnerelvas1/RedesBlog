<?php

namespace App\Repositories;

use App\Models\User;

/**
 * Persistence and lookups for user accounts.
 */
final class UserRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);
        $user->save();

        return $user;
    }

    /**
     * Lookup is case-insensitive: `username` is a `citext` column.
     */
    public function findByUsername(string $username): ?User
    {
        return User::query()->where('username', $username)->first();
    }

    public function setAvatarPath(User $user, ?string $path): User
    {
        $user->avatar_path = $path;
        $user->save();

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
