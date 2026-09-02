<?php

namespace App\Http\Middleware;

use App\Http\Resources\CommunitySummaryResource;
use App\Repositories\CommunityRepository;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private readonly CommunityRepository $communities) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'bio' => $user->bio,
                    'avatar_url' => $user->avatar_url,
                ],
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error' => fn (): ?string => $request->session()->get('error'),
            ],
            'sidebar' => [
                // Rendered by `AppLayout` on every page, so it is resolved on
                // every full visit (a closure keeps it off partial reloads).
                'communities' => fn (): array => $user === null
                    ? []
                    : CommunitySummaryResource::collection(
                        $this->communities->forSidebar($user),
                    )->resolve($request),
            ],
            'appearance' => $request->cookie('appearance') ?? 'system',
        ];
    }
}
