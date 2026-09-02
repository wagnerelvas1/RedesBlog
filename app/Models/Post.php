<?php

namespace App\Models;

use App\Models\Concerns\HasVotes;
use App\Policies\PostPolicy;
use Carbon\CarbonInterface;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $community_id
 * @property int|null $user_id
 * @property string $title
 * @property string|null $body
 * @property string $slug
 * @property bool $is_pinned
 * @property CarbonInterface|null $pinned_at
 * @property int|null $pinned_by
 * @property int $score
 * @property int $upvotes_count
 * @property int $downvotes_count
 * @property int $comments_count
 * @property float|null $hot_rank
 * @property CarbonInterface|null $edited_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 */
#[UsePolicy(PostPolicy::class)]
#[Fillable(['title', 'body'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasVotes, SoftDeletes;

    /**
     * @return BelongsTo<Community, $this>
     */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pinnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->orderBy('position')
            ->orderBy('id');
    }

    /**
     * Top-level comments only.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_posts')->withTimestamps();
    }

    /**
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePinnedFirst(Builder $query): Builder
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('pinned_at');
    }

    /**
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeForCommunity(Builder $query, Community $community): Builder
    {
        return $query->where('community_id', $community->id);
    }

    /**
     * Applies one of the feed sort orders.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeSort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'new' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'top' => $query->orderByDesc('score')->orderByDesc('id'),
            'controversial' => $query
                ->orderByRaw(
                    '(upvotes_count + downvotes_count) * '
                    .'(LEAST(upvotes_count, downvotes_count)::float / GREATEST(upvotes_count, downvotes_count, 1)) DESC'
                )
                ->orderByDesc('id'),
            default => $query->orderByDesc('hot_rank')->orderByDesc('id'),
        };
    }

    /**
     * Restricts a "top" query to a time window.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeWithinRange(Builder $query, ?string $range): Builder
    {
        $since = match ($range) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => null,
        };

        return $since === null ? $query : $query->where('created_at', '>=', $since);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'pinned_at' => 'datetime',
            'edited_at' => 'datetime',
            'score' => 'integer',
            'upvotes_count' => 'integer',
            'downvotes_count' => 'integer',
            'comments_count' => 'integer',
            'hot_rank' => 'float',
        ];
    }
}
