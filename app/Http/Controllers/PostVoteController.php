<?php

namespace App\Http\Controllers;

use App\Http\Requests\Vote\StoreVoteRequest;
use App\Models\Post;
use App\Services\VoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostVoteController extends Controller
{
    public function store(StoreVoteRequest $request, Post $post, VoteService $service): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $result = $service->cast($user, $post, (int) $request->integer('value'));

        return back()->with('vote', $result->toArray());
    }

    /**
     * Clearing a vote carries no body, so there is no FormRequest here; the
     * policy is checked inline instead.
     */
    public function destroy(Request $request, Post $post, VoteService $service): RedirectResponse
    {
        Gate::authorize('vote', $post);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $result = $service->clear($user, $post);

        return back()->with('vote', $result->toArray());
    }
}
