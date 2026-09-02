<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Tree loading and persistence for comments.
 *
 * Trees are loaded level by level with `whereIn(parent_id, …)`, so rendering a
 * thread of any shape costs one query per level rather than one per node.
 */
final class CommentRepository
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Post $post, User $author, ?Comment $parent, array $attributes): Comment
    {
        $comment = new Comment($attributes);
        $comment->post()->associate($post);
        $comment->author()->associate($author);

        if ($parent !== null) {
            $comment->parent()->associate($parent);
            $comment->depth = $parent->depth + 1;
            $comment->path = $parent->childPath();
        } else {
            $comment->depth = 0;
            $comment->path = '/';
        }

        $comment->save();

        return $comment;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Comment $comment, array $attributes): Comment
    {
        $comment->fill($attributes);
        $comment->edited_at = now();
        $comment->save();

        return $comment;
    }

    /**
     * Loads the top-level comments plus up to `$childLimitPerNode` replies at
     * each level, down to the render depth cap.
     *
     * @param  array{sort?: string|null}  $filters
     * @return Collection<int, Comment>
     */
    public function treeForPost(
        Post $post,
        array $filters,
        ?User $viewer,
        int $rootLimit = 50,
        int $childLimitPerNode = 5,
    ): Collection {
        $sort = $filters['sort'] ?? 'best';

        $roots = $this->baseQuery($viewer)
            ->forPost($post)
            ->topLevel()
            ->sort($sort)
            ->limit($rootLimit)
            ->get();

        $this->loadReplies($roots, $sort, $viewer, $childLimitPerNode);
        $this->bindPost($roots, $post);

        return $roots;
    }

    /**
     * Loads a thread rooted at one comment, for the "continue this thread" and
     * deep-link views.
     *
     * @param  array{sort?: string|null}  $filters
     * @return Collection<int, Comment>
     */
    public function threadFrom(Comment $root, array $filters, ?User $viewer, int $childLimitPerNode = 5): Collection
    {
        $sort = $filters['sort'] ?? 'best';

        $loaded = $this->baseQuery($viewer)->whereKey($root->id)->get();

        $this->loadReplies($loaded, $sort, $viewer, $childLimitPerNode);

        $post = $root->relationLoaded('post') ? $root->post : $root->post()->first();

        if ($post instanceof Post) {
            $this->bindPost($loaded, $post);
        }

        return $loaded;
    }

    /**
     * Direct replies of one comment, paginated for "load more replies".
     *
     * @param  array{sort?: string|null, cursor?: string|null}  $filters
     * @return CursorPaginator<int, Comment>
     */
    public function subtree(Comment $parent, array $filters, ?User $viewer, ?Post $post = null): CursorPaginator
    {
        /** @var CursorPaginator<int, Comment> $paginator */
        $paginator = $this->baseQuery($viewer)
            ->where('parent_id', $parent->id)
            ->sort($filters['sort'] ?? 'best')
            ->cursorPaginate(20, ['*'], 'cursor', $filters['cursor'] ?? null)
            ->withQueryString();

        if ($post instanceof Post) {
            $this->bindPost(new Collection($paginator->items()), $post);
        }

        return $paginator;
    }

    /**
     * Number of non-deleted comments on a post, used to reconcile the cached
     * `posts.comments_count`.
     */
    public function countForPost(Post $post): int
    {
        return Comment::query()->where('post_id', $post->id)->count();
    }

    public function countReplies(Comment $comment): int
    {
        return Comment::query()->where('parent_id', $comment->id)->count();
    }

    /**
     * Points every node at the post (and its community) that is already in
     * memory, so the policy checks made while serialising the tree do not
     * re-query them once per node.
     *
     * @param  Collection<int, Comment>  $nodes
     */
    private function bindPost(Collection $nodes, Post $post): void
    {
        $post->loadMissing('community');

        foreach ($nodes as $node) {
            $node->setRelation('post', $post);

            $replies = $node->relationLoaded('replies') ? $node->getRelation('replies') : null;

            if ($replies instanceof Collection) {
                $this->bindPost($replies, $post);
            }
        }
    }

    /**
     * @return Builder<Comment>
     */
    private function baseQuery(?User $viewer): Builder
    {
        return Comment::query()
            ->withTrashed()
            ->with(['author', 'attachment'])
            ->withViewerVote($viewer)
            ->addSelect('comments.*');
    }

    /**
     * Recursively attaches replies to each node, one query per level.
     *
     * @param  Collection<int, Comment>  $nodes
     */
    private function loadReplies(
        Collection $nodes,
        string $sort,
        ?User $viewer,
        int $limitPerNode,
    ): void {
        if ($nodes->isEmpty()) {
            return;
        }

        $parents = $nodes->filter(
            fn (Comment $node): bool => $node->depth + 1 < Comment::RENDER_DEPTH_CAP
                && $node->replies_count > 0,
        );

        if ($parents->isEmpty()) {
            $nodes->each(fn (Comment $node) => $node->setRelation('replies', new Collection));

            return;
        }

        $children = $this->baseQuery($viewer)
            ->whereIn('parent_id', $parents->modelKeys())
            ->sort($sort)
            ->get()
            ->groupBy('parent_id');

        $nextLevel = new Collection;

        foreach ($nodes as $node) {
            /** @var Collection<int, Comment> $group */
            $group = $children->get($node->id) ?? new Collection;
            $kept = $group->take($limitPerNode)->values();

            $node->setRelation('replies', $kept);
            $node->setAttribute('has_more_replies', $group->count() > $kept->count());

            foreach ($kept as $child) {
                $nextLevel->push($child);
            }
        }

        $this->loadReplies($nextLevel, $sort, $viewer, $limitPerNode);
    }
}
