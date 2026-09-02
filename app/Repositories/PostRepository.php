<?php

namespace App\Repositories;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Feed queries and persistence for posts.
 *
 * Every listing eager-loads the author, community and attachments and folds the
 * viewer's vote/saved state into the same query, so rendering N posts stays at
 * a constant number of queries.
 */
final class PostRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Community $community, User $author, array $attributes): Post
    {
        $post = new Post($attributes);
        $post->community()->associate($community);
        $post->author()->associate($author);
        $post->slug = Str::slug(Str::limit((string) $attributes['title'], 80, ''));
        $post->save();

        // The id is only known after the insert; keep the slug human-readable.
        $post->slug = $post->id.'-'.($post->slug !== '' ? $post->slug : 'post');
        $post->save();

        return $post;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Post $post, array $attributes): Post
    {
        $post->fill($attributes);
        $post->edited_at = now();
        $post->save();

        return $post;
    }

    /**
     * @param  array{sort?: string|null, range?: string|null, cursor?: string|null}  $filters
     * @return CursorPaginator<int, Post>
     */
    public function feedForCommunity(Community $community, array $filters, ?User $viewer): CursorPaginator
    {
        $query = $this->baseFeedQuery($viewer)->forCommunity($community);

        return $this->paginateFeed($query, $filters, pinnedFirst: true);
    }

    /**
     * Home feed: the user's communities, or everything for guests and users
     * who have not joined anything yet.
     *
     * @param  array{sort?: string|null, range?: string|null, cursor?: string|null}  $filters
     * @return CursorPaginator<int, Post>
     */
    public function aggregatedFeed(?User $viewer, array $filters): CursorPaginator
    {
        $query = $this->baseFeedQuery($viewer);

        if ($viewer !== null) {
            $communityIds = $viewer->communities()
                ->wherePivotNull('banned_at')
                ->pluck('communities.id');

            if ($communityIds->isNotEmpty()) {
                $query->whereIn('community_id', $communityIds);
            }
        }

        return $this->paginateFeed($query, $filters, pinnedFirst: false);
    }

    /**
     * @param  array{sort?: string|null, range?: string|null, cursor?: string|null}  $filters
     * @return CursorPaginator<int, Post>
     */
    public function savedFor(User $user, array $filters): CursorPaginator
    {
        $query = $this->baseFeedQuery($user)
            ->whereIn(
                'posts.id',
                fn ($inner) => $inner
                    ->select('post_id')
                    ->from('saved_posts')
                    ->where('user_id', $user->id),
            )
            ->orderByDesc('posts.id');

        /** @var CursorPaginator<int, Post> $paginator */
        $paginator = $query->cursorPaginate(20, ['*'], 'cursor', $filters['cursor'] ?? null)
            ->withQueryString();

        return $paginator;
    }

    /**
     * @param  array{sort?: string|null, range?: string|null, cursor?: string|null}  $filters
     * @return CursorPaginator<int, Post>
     */
    public function feedForAuthor(User $author, array $filters, ?User $viewer): CursorPaginator
    {
        $query = $this->baseFeedQuery($viewer)
            ->where('user_id', $author->id)
            ->orderByDesc('posts.id');

        /** @var CursorPaginator<int, Post> $paginator */
        $paginator = $query->cursorPaginate(20, ['*'], 'cursor', $filters['cursor'] ?? null)
            ->withQueryString();

        return $paginator;
    }

    /**
     * Loads a single post with everything the detail page needs.
     */
    public function loadForDisplay(Post $post, ?User $viewer): Post
    {
        $post->loadMissing(['author', 'community', 'attachments']);

        $post->setAttribute('viewer_vote', $this->viewerVoteFor($post, $viewer));
        $post->setAttribute('is_saved', $this->isSaved($post, $viewer));

        return $post;
    }

    public function setPinned(Post $post, ?User $admin): void
    {
        $post->is_pinned = $admin !== null;
        $post->pinned_at = $admin === null ? null : now();
        $post->pinned_by = $admin?->id;
        $post->save();
    }

    public function save(User $user, Post $post): void
    {
        $post->savedByUsers()->syncWithoutDetaching([$user->id]);
    }

    public function unsave(User $user, Post $post): void
    {
        $post->savedByUsers()->detach($user->id);
    }

    public function isSaved(Post $post, ?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $post->savedByUsers()->whereKey($user->id)->exists();
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    /**
     * @return Builder<Post>
     */
    private function baseFeedQuery(?User $viewer): Builder
    {
        return Post::query()
            ->with(['author', 'community', 'attachments'])
            ->withViewerVote($viewer)
            ->addSelect('posts.*')
            ->when($viewer !== null, fn (Builder $query) => $query->withExists([
                'savedByUsers as is_saved' => fn ($inner) => $inner->where('users.id', $viewer?->id),
            ]));
    }

    /**
     * @param  Builder<Post>  $query
     * @param  array{sort?: string|null, range?: string|null, cursor?: string|null}  $filters
     * @return CursorPaginator<int, Post>
     */
    private function paginateFeed(Builder $query, array $filters, bool $pinnedFirst): CursorPaginator
    {
        $sort = $filters['sort'] ?? 'hot';

        if ($sort === 'top') {
            $query->withinRange($filters['range'] ?? 'all');
        }

        if ($pinnedFirst) {
            $query->pinnedFirst();
        }

        $query->sort($sort);

        /** @var CursorPaginator<int, Post> $paginator */
        $paginator = $query->cursorPaginate(20, ['*'], 'cursor', $filters['cursor'] ?? null)
            ->withQueryString();

        return $paginator;
    }

    private function viewerVoteFor(Post $post, ?User $viewer): int
    {
        if ($viewer === null) {
            return 0;
        }

        $vote = $post->votes()->where('user_id', $viewer->id)->first();

        return $vote === null ? 0 : $vote->value;
    }
}
