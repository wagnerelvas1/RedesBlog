<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string $email
 * @property CarbonInterface|null $email_verified_at
 * @property string $password
 * @property string|null $avatar_path
 * @property string|null $bio
 * @property string|null $remember_token
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read string|null $avatar_url
 */
#[Fillable(['name', 'username', 'email', 'password', 'bio'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Public URL of the stored avatar, or null so the UI renders initials.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->avatar_path === null
                ? null
                : Storage::disk((string) config('filesystems.default'))->url($this->avatar_path),
        );
    }

    /**
     * Communities this user created; blocks account deletion while non-empty.
     *
     * @return HasMany<Community, $this>
     */
    public function createdCommunities(): HasMany
    {
        return $this->hasMany(Community::class, 'created_by');
    }

    /**
     * @return BelongsToMany<Community, $this, CommunityMember>
     */
    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class)
            ->using(CommunityMember::class)
            ->withPivot(['id', 'role', 'is_creator', 'banned_at', 'banned_by'])
            ->withTimestamps();
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
