<?php

namespace App\Support;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Inertia\Inertia;

/**
 * Shapes a cursor-paginated post feed into the props every feed page expects.
 *
 * `posts` is a merging prop so infinite scroll appends instead of replacing.
 */
final class FeedPayload
{
    /**
     * @param  CursorPaginator<int, Post>  $paginator
     * @return array<string, mixed>
     */
    public static function make(CursorPaginator $paginator): array
    {
        return [
            'posts' => Inertia::merge(
                fn (): array => PostResource::collection($paginator)->resolve(),
            ),
            'pagination' => [
                'next_cursor' => $paginator->nextCursor()?->encode(),
                'prev_cursor' => $paginator->previousCursor()?->encode(),
            ],
        ];
    }
}
