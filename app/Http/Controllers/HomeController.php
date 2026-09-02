<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\IndexPostsRequest;
use App\Repositories\PostRepository;
use App\Support\FeedPayload;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Aggregated feed shown at `/`: posts from the communities the viewer joined,
 * or everything when they are a guest or have joined nothing.
 */
class HomeController extends Controller
{
    public function __invoke(IndexPostsRequest $request, PostRepository $posts): Response
    {
        /** @var array{sort?: string|null, range?: string|null, cursor?: string|null} $filters */
        $filters = $request->validated();

        return Inertia::render('home', [
            ...FeedPayload::make($posts->aggregatedFeed($request->user(), $filters)),
            'filters' => [
                'sort' => $filters['sort'] ?? 'hot',
                'range' => $filters['range'] ?? 'all',
            ],
        ]);
    }
}
