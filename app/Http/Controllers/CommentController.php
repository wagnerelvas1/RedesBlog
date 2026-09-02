<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\IndexCommentsRequest;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Community;
use App\Models\Post;
use App\Repositories\CommentRepository;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentRepository $comments,
    ) {}

    /**
     * Lazy-loading endpoint: returns one subtree (or the roots) as JSON so the
     * client can merge "load more replies" without a full page visit.
     */
    public function index(IndexCommentsRequest $request, Community $community, Post $post): JsonResponse
    {
        /** @var array{sort?: string|null, cursor?: string|null} $filters */
        $filters = $request->validated();
        $parentId = $request->input('parent_id');

        if ($parentId !== null) {
            $parent = Comment::query()->where('post_id', $post->id)
                ->where('id', $parentId)
                ->firstOrFail();
            $page = $this->comments->subtree($parent, $filters, $request->user(), $post);

            return response()->json([
                'comments' => CommentResource::collection($page)->resolve(),
                'next_cursor' => $page->nextCursor()?->encode(),
            ]);
        }

        return response()->json([
            'comments' => CommentResource::collection(
                $this->comments->treeForPost($post, $filters, $request->user()),
            )->resolve(),
            'next_cursor' => null,
        ]);
    }

    public function store(
        StoreCommentRequest $request,
        Community $community,
        Post $post,
        CommentService $service,
    ): RedirectResponse {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $parentId = $request->input('parent_id');
        $parent = $parentId === null
            ? null
            : Comment::query()->where('post_id', $post->id)->where('id', $parentId)->first();

        $image = $request->file('image');

        $service->create(
            $post,
            $user,
            $parent,
            $request->validated(),
            $image instanceof UploadedFile ? $image : null,
        );

        return back()->with('success', 'Comment posted.');
    }

    public function update(
        UpdateCommentRequest $request,
        Community $community,
        Post $post,
        Comment $comment,
        CommentService $service,
    ): RedirectResponse {
        $image = $request->file('image');

        $service->update(
            $comment,
            $request->validated(),
            $image instanceof UploadedFile ? $image : null,
            $request->boolean('keep_image', true),
        );

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(
        Request $request,
        Community $community,
        Post $post,
        Comment $comment,
        CommentService $service,
    ): RedirectResponse {
        $service->delete($comment);

        return back()->with('success', 'Comment deleted.');
    }
}
