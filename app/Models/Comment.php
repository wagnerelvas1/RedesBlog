<?php

namespace App\Models;

use App\Models\Concerns\HasVotes;
use App\Policies\CommentPolicy;
use Carbon\CarbonInterface;
use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $post_id
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property string $body
 * @property int $depth
 * @property string $path
 * @property int $score
 * @property int $upvotes_count
 * @property int $downvotes_count
 * @property int $replies_count
 * @property float|null $best_rank
 * @property CarbonInterface|null $edited_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property CarbonInterface|null $deleted_at
 */
#[UsePolicy(CommentPolicy::class)]
#[Fillable(['body'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory, HasVotes, SoftDeletes;

    /**
     * Deeper replies are still stored, but rendered flattened behind a
     * "continue this thread" link.
     */
    public const RENDER_DEPTH_CAP = 8;

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Comment, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * A comment carries at most one image.
     *
     * @return MorphOne<Attachment, $this>
     */
    public function attachment(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable');
    }

    /**
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeForPost(Builder $query, Post $post): Builder
    {
        return $query->where('post_id', $post->id);
    }

    /**
     * @param  Builder<Comment>  $query
     * @return Builder<Comment>
     */
    public function scopeSort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'new' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'old' => $query->orderBy('created_at')->orderBy('id'),
            'top' => $query->orderByDesc('score')->orderByDesc('id'),
            'controversial' => $query
                ->orderByRaw(
                    '(upvotes_count + downvotes_count) * '
                    .'(LEAST(upvotes_count, downvotes_count)::float / GREATEST(upvotes_count, downvotes_count, 1)) DESC'
                )
                ->orderByDesc('id'),
            default => $query->orderByRaw('best_rank DESC NULLS LAST')->orderByDesc('id'),
        };
    }

    /**
     * The path a child of this comment would carry.
     */
    public function childPath(): string
    {
        return $this->path.$this->id.'/';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'score' => 'integer',
            'upvotes_count' => 'integer',
            'downvotes_count' => 'integer',
            'replies_count' => 'integer',
            'best_rank' => 'float',
            'edited_at' => 'datetime',
        ];
    }
}
