<?php

namespace App\Http\Controllers;

use App\Enums\CommunityRole;
use App\Exceptions\CommunityException;
use App\Http\Requests\Community\IndexMembersRequest;
use App\Http\Requests\Community\UpdateMemberRequest;
use App\Http\Resources\CommunityResource;
use App\Http\Resources\MemberResource;
use App\Models\Community;
use App\Models\User;
use App\Repositories\CommunityRepository;
use App\Services\CommunityService;
use App\Support\PaginatedPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunityMemberController extends Controller
{
    public function __construct(
        private readonly CommunityRepository $communities,
    ) {}

    public function index(IndexMembersRequest $request, Community $community): Response
    {
        /** @var array{search?: string|null, role?: string|null} $filters */
        $filters = $request->validated();

        return Inertia::render('communities/settings/members', [
            'community' => new CommunityResource($community),
            'members' => PaginatedPayload::make(
                $this->communities->members($community, $filters),
                MemberResource::class,
            ),
            'filters' => [
                'search' => $filters['search'] ?? null,
                'role' => $filters['role'] ?? null,
            ],
            'permissions' => [
                'can_manage_admins' => $request->user()?->can('manageAdmins', $community) === true,
            ],
        ]);
    }

    public function update(
        UpdateMemberRequest $request,
        Community $community,
        User $user,
        CommunityService $service,
    ): RedirectResponse {
        $actor = $request->user();

        if ($actor === null) {
            abort(403);
        }

        try {
            if ($request->has('role')) {
                $role = CommunityRole::from((string) $request->string('role'));

                $role === CommunityRole::Admin
                    ? $service->grantAdmin($community, $user)
                    : $service->revokeAdmin($community, $user);
            }

            if ($request->has('banned')) {
                $request->boolean('banned')
                    ? $service->banMember($community, $actor, $user)
                    : $service->unbanMember($community, $user);
            }
        } catch (CommunityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Member updated.');
    }

    public function destroy(
        Request $request,
        Community $community,
        User $user,
        CommunityService $service,
    ): RedirectResponse {
        $actor = $request->user();

        if ($actor === null) {
            abort(403);
        }

        try {
            $service->removeMember($community, $actor, $user);
        } catch (CommunityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Member removed.');
    }
}
