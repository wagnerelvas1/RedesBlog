<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registration, profile maintenance and account removal.
 *
 * Avatars are stored as a plain `users.avatar_path` key rather than through the
 * `attachments` table, which is reserved for post and comment images.
 */
final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AttachmentService $attachments,
    ) {}

    /**
     * Creates the account and signs the new user in.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): User
    {
        $user = DB::transaction(fn (): User => $this->users->create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]));

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(
        User $user,
        array $data,
        ?UploadedFile $avatar = null,
        bool $removeAvatar = false,
    ): User {
        return DB::transaction(function () use ($user, $data, $avatar, $removeAvatar): User {
            $this->users->update($user, [
                'name' => $data['name'],
                'username' => $data['username'],
                'bio' => $data['bio'] ?? null,
            ]);

            if ($removeAvatar || $avatar !== null) {
                $previous = $user->avatar_path;

                $path = $avatar === null
                    ? null
                    : $this->attachments->storeStandalone($avatar, 'avatars');

                $this->users->setAvatarPath($user, $path);
                $this->attachments->deleteStandalone($previous);
            }

            return $user->refresh();
        });
    }

    /**
     * Permanently removes the account.
     *
     * @throws ValidationException when the user still owns a community.
     */
    public function deleteAccount(User $user): void
    {
        $owned = $user->createdCommunities()->pluck('name')->all();

        if ($owned !== []) {
            throw ValidationException::withMessages([
                'password' => 'Transfer or delete these communities first: '.implode(', ', $owned).'.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            $avatar = $user->avatar_path;

            $this->users->delete($user);
            $this->attachments->deleteStandalone($avatar);
        });
    }
}
