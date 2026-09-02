<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('settings/profile', [
            'profile' => [
                'name' => $user?->name,
                'username' => $user?->username,
                'email' => $user?->email,
                'bio' => $user?->bio,
                'avatar_url' => $user?->avatar_url,
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request, AuthService $auth): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $auth->updateProfile(
            $user,
            $request->validated(),
            $request->file('avatar') instanceof UploadedFile
                ? $request->file('avatar')
                : null,
            $request->boolean('remove_avatar'),
        );

        return back()->with('success', 'Profile updated.');
    }

    public function destroy(Request $request, AuthService $auth): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $auth->deleteAccount($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('success', 'Your account has been deleted.');
    }

    /**
     * Public profile at `/u/{username}`.
     */
    public function show(User $user): Response
    {
        return Inertia::render('u/profile', [
            'profile' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'bio' => $user->bio,
                'avatar_url' => $user->avatar_url,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }
}
