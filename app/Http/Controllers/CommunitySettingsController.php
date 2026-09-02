<?php

namespace App\Http\Controllers;

use App\Http\Requests\Community\DeleteCommunityRequest;
use App\Http\Requests\Community\UpdateCommunitySettingsRequest;
use App\Http\Resources\CommunityResource;
use App\Models\Community;
use App\Services\CommunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class CommunitySettingsController extends Controller
{
    public function edit(Community $community): Response
    {
        $community->loadMissing('creator');

        return Inertia::render('communities/settings/edit', [
            'community' => new CommunityResource($community),
        ]);
    }

    public function update(
        UpdateCommunitySettingsRequest $request,
        Community $community,
        CommunityService $service,
    ): RedirectResponse {
        $service->updateSettings(
            $community,
            $request->validated(),
            $this->file($request, 'avatar'),
            $this->file($request, 'banner'),
            $request->boolean('remove_avatar'),
            $request->boolean('remove_banner'),
        );

        return back()->with('success', 'Community updated.');
    }

    public function destroy(
        DeleteCommunityRequest $request,
        Community $community,
        CommunityService $service,
    ): RedirectResponse {
        $service->delete($community);

        return to_route('communities.index')->with('success', 'Community deleted.');
    }

    private function file(Request $request, string $key): ?UploadedFile
    {
        $file = $request->file($key);

        return $file instanceof UploadedFile ? $file : null;
    }
}
