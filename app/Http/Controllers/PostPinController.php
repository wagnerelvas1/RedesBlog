<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pinning carries no request body, so the policy guards the route directly.
 */
class PostPinController extends Controller
{
    public function store(Request $request, Community $community, Post $post, PostService $service): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $service->pin($post, $user);

        return back()->with('success', 'Post pinned.');
    }

    public function destroy(Community $community, Post $post, PostService $service): RedirectResponse
    {
        $service->unpin($post);

        return back()->with('success', 'Post unpinned.');
    }
}
