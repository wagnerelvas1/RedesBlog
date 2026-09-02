<?php

namespace App\Http\Controllers;

use App\Http\Requests\Community\IndexCommunitiesRequest;
use App\Http\Requests\Community\StoreCommunityRequest;
use App\Http\Requests\Post\IndexPostsRequest;
use App\Http\Resources\CommunityResource;
use App\Http\Resources\CommunitySummaryResource;
use App\Models\Community;
use App\Repositories\CommunityRepository;
use App\Repositories\PostRepository;
use App\Services\CommunityService;
use App\Support\FeedPayload;
use App\Support\PaginatedPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller
{
    public function __construct(
        private readonly CommunityRepository $communities,
        private readonly PostRepository $posts,
    ) {}

    public function index(IndexCommunitiesRequest $request): Response
    {
        /** @var array{search?: string|null, sort?: string|null, filter?: string|null} $filters */
        $filters = $request->validated();

        $communities = $this->communities->paginateForIndex($filters, $request->user());

        return Inertia::render('communities/index', [
            'communities' => PaginatedPayload::make($communities, CommunitySummaryResource::class),
            'filters' => [
                'search' => $filters['search'] ?? null,
                'sort' => $filters['sort'] ?? 'members',
                'filter' => $filters['filter'] ?? 'all',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('communities/create');
    }

    public function store(StoreCommunityRequest $request, CommunityService $service): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $community = $service->create(
            $user,
            $request->validated(),
            $this->file($request, 'avatar'),
            $this->file($request, 'banner'),
        );

        return to_route('communities.show', $community)
            ->with('success', 'Community created.');
    }

    public function show(IndexPostsRequest $request, Community $community): Response
    {
        $community->loadMissing('creator');

        /** @var array{sort?: string|null, range?: string|null, cursor?: string|null} $filters */
        $filters = $request->validated();

        return Inertia::render('communities/show', [
            'community' => new CommunityResource($community),
            ...$this->viewerState($request, $community),
            ...FeedPayload::make(
                $this->posts->feedForCommunity($community, $filters, $request->user()),
            ),
            'filters' => [
                'sort' => $filters['sort'] ?? 'hot',
                'range' => $filters['range'] ?? 'all',
            ],
        ]);
    }

    public function about(Request $request, Community $community): Response
    {
        $community->loadMissing('creator');

        return Inertia::render('communities/about', [
            'community' => new CommunityResource($community),
            ...$this->viewerState($request, $community),
        ]);
    }

    /**
     * Membership and permission flags the UI needs to decide what to render.
     *
     * @return array<string, mixed>
     */
    private function viewerState(Request $request, Community $community): array
    {
        $user = $request->user();
        $membership = $this->communities->membership($community, $user);

        return [
            'membership' => [
                'is_member' => $membership !== null && ! $membership->isBanned(),
                'is_admin' => $membership?->isAdmin() === true,
                'is_creator' => $membership?->is_creator === true,
                'is_banned' => $membership?->isBanned() === true,
                'role' => $membership?->role->value,
            ],
            'permissions' => [
                'can_update' => $user?->can('update', $community) === true,
                'can_manage_members' => $user?->can('manageMembers', $community) === true,
                'can_manage_admins' => $user?->can('manageAdmins', $community) === true,
                'can_delete' => $user?->can('delete', $community) === true,
                'can_post' => $user?->can('post', $community) === true,
            ],
        ];
    }

    private function file(Request $request, string $key): ?UploadedFile
    {
        $file = $request->file($key);

        return $file instanceof UploadedFile ? $file : null;
    }
}
