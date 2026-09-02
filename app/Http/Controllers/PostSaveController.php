<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Save and unsave carry no request body, so no FormRequest applies; the route
 * is guarded by `auth` plus the `save` policy ability.
 */
class PostSaveController extends Controller
{
    public function store(Request $request, Post $post, PostService $service): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $service->save($user, $post);

        return back()->with('success', 'Post saved.');
    }

    public function destroy(Request $request, Post $post, PostService $service): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $service->unsave($user, $post);

        return back()->with('success', 'Post removed from your saved list.');
    }
}
