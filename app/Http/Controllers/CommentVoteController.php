<?php

namespace App\Http\Controllers;

use App\Http\Requests\Vote\StoreVoteRequest;
use App\Models\Comment;
use App\Services\VoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentVoteController extends Controller
{
    public function store(StoreVoteRequest $request, Comment $comment, VoteService $service): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $result = $service->cast($user, $comment, (int) $request->integer('value'));

        return back()->with('vote', $result->toArray());
    }

    /**
     * Clearing a vote carries no body, so there is no FormRequest here; the
     * policy is checked inline instead.
     */
    public function destroy(Request $request, Comment $comment, VoteService $service): RedirectResponse
    {
        Gate::authorize('vote', $comment);

        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $result = $service->clear($user, $comment);

        return back()->with('vote', $result->toArray());
    }
}
