<?php

namespace App\Models;

use App\Enums\CommunityRole;
use App\Policies\CommunityPolicy;
use Carbon\CarbonInterface;
use Database\Factories\CommunityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string $title
 * @property string|null $description
 * @property string|null $rules
 * @property string|null $avatar_path
 * @property string|null $banner_path
 * @property bool $is_private
 * @property int $created_by
 * @property int $members_count
 * @property int $posts_count
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read string|null $avatar_url
 * @property-read string|null $banner_url
 */
#[UsePolicy(CommunityPolicy::class)]
// `name` is deliberately absent: a community name can never be changed.
#[Fillable(['title', 'description', 'rules'])]
class Community extends Model
{
    /** @use HasFactory<CommunityFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsToMany<User, $this, CommunityMember>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CommunityMember::class)
            ->withPivot(['id', 'role', 'is_creator', 'banned_at', 'banned_by'])
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this, CommunityMember>
     */
    public function admins(): BelongsToMany
    {
        return $this->members()
            ->wherePivotNull('banned_at')
            ->where(fn (Builder $query) => $query
                ->where('community_user.role', CommunityRole::Admin->value)
                ->orWhere('community_user.is_creator', true));
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->publicUrl($this->avatar_path));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function bannerUrl(): Attribute
    {
        return Attribute::make(get: fn (): ?string => $this->publicUrl($this->banner_path));
    }

    private function publicUrl(?string $path): ?string
    {
        return $path === null
            ? null
            : Storage::disk((string) config('filesystems.default'))->url($path);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'members_count' => 'integer',
            'posts_count' => 'integer',
        ];
    }
}
