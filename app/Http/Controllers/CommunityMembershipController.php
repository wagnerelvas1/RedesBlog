<?php

namespace App\Http\Controllers;

use App\Exceptions\CommunityException;
use App\Models\Community;
use App\Services\CommunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Join and leave carry no request body, so they are guarded by policies on the
 * route instead of a FormRequest.
 */
class CommunityMembershipController extends Controller
{
    public function store(Request $request, Community $community, CommunityService $service): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        try {
            $service->join($community, $user);
        } catch (CommunityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Joined '.$community->name.'.');
    }

    public function destroy(Request $request, Community $community, CommunityService $service): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        try {
            $service->leave($community, $user);
        } catch (CommunityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Left '.$community->name.'.');
    }
}
