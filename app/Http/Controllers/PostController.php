<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\IndexCommentsRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\CommunityResource;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use App\Services\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly CommentRepository $comments,
    ) {}

    public function create(Request $request, Community $community): Response
    {
        $request->user()?->can('post', $community) === true || abort(403);

        return Inertia::render('posts/create', [
            'community' => new CommunityResource($community),
        ]);
    }

    public function store(
        StorePostRequest $request,
        Community $community,
        PostService $service,
    ): RedirectResponse {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $post = $service->create(
            $community,
            $user,
            $request->validated(),
            $this->images($request),
        );

        return to_route('posts.show', [$community, $post])
            ->with('success', 'Post published.');
    }

    public function show(IndexCommentsRequest $request, Community $community, Post $post): Response
    {
        /** @var array{sort?: string|null} $filters */
        $filters = $request->validated();
        $viewer = $request->user();

        return Inertia::render('posts/show', [
            'community' => new CommunityResource($community->loadMissing('creator')),
            'post' => new PostResource($this->posts->loadForDisplay($post, $viewer)),
            'comments' => CommentResource::collection(
                $this->comments->treeForPost($post, $filters, $viewer),
            )->resolve(),
            'commentSort' => $filters['sort'] ?? 'best',
            'canComment' => $viewer?->can('create', [Comment::class, $post]) === true,
        ]);
    }

    public function edit(Request $request, Community $community, Post $post): Response
    {
        return Inertia::render('posts/edit', [
            'community' => new CommunityResource($community),
            'post' => new PostResource(
                $this->posts->loadForDisplay($post, $request->user()),
            ),
        ]);
    }

    public function update(
        UpdatePostRequest $request,
        Community $community,
        Post $post,
        PostService $service,
    ): RedirectResponse {
        /** @var array<int, int> $keep */
        $keep = array_map('intval', (array) $request->input('existing_images', []));

        $service->update($post, $request->validated(), $keep, $this->images($request));

        return to_route('posts.show', [$community, $post])
            ->with('success', 'Post updated.');
    }

    public function destroy(Community $community, Post $post, PostService $service): RedirectResponse
    {
        $service->delete($post);

        return to_route('communities.show', $community)->with('success', 'Post deleted.');
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function images(Request $request): array
    {
        /** @var array<int, UploadedFile>|UploadedFile|null $files */
        $files = $request->file('images');

        if ($files === null) {
            return [];
        }

        return array_values(is_array($files) ? $files : [$files]);
    }
}
