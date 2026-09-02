<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\IndexPostsRequest;
use App\Repositories\PostRepository;
use App\Support\FeedPayload;
use Inertia\Inertia;
use Inertia\Response;

class SavedPostController extends Controller
{
    public function index(IndexPostsRequest $request, PostRepository $posts): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        /** @var array{sort?: string|null, range?: string|null, cursor?: string|null} $filters */
        $filters = $request->validated();

        return Inertia::render('saved/index', [
            ...FeedPayload::make($posts->savedFor($user, $filters)),
        ]);
    }
}
