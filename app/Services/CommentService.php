<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CommentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Business rules for the comment tree, including the denormalised counters on
 * the parent comment and the post.
 */
final class CommentService
{
    public function __construct(
        private readonly CommentRepository $comments,
        private readonly AttachmentService $attachments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        Post $post,
        User $author,
        ?Comment $parent,
        array $data,
        ?UploadedFile $image = null,
    ): Comment {
        return DB::transaction(function () use ($post, $author, $parent, $data, $image): Comment {
            $comment = $this->comments->create($post, $author, $parent, [
                'body' => $data['body'] ?? '',
            ]);

            if ($image !== null) {
                $this->attachments->attachOne($comment, $image, 'comments/'.$comment->id);
            }

            if ($parent !== null) {
                $parent->increment('replies_count');
            }

            $post->increment('comments_count');

            return $comment->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        Comment $comment,
        array $data,
        ?UploadedFile $image = null,
        bool $keepImage = true,
    ): Comment {
        return DB::transaction(function () use ($comment, $data, $image, $keepImage): Comment {
            $this->comments->update($comment, ['body' => $data['body'] ?? '']);

            if ($image !== null) {
                $this->attachments->detachAll($comment);
                $this->attachments->attachOne($comment, $image, 'comments/'.$comment->id);
            } elseif (! $keepImage) {
                $this->attachments->detachAll($comment);
            }

            return $comment->refresh();
        });
    }

    /**
     * Soft delete. A comment that still has replies keeps its row so the
     * subtree survives and renders as "[removed]".
     */
    public function delete(Comment $comment): void
    {
        DB::transaction(function () use ($comment): void {
            $post = $comment->post()->first();
            $parent = $comment->parent()->first();

            $comment->delete();

            if ($post instanceof Post && $post->comments_count > 0) {
                $post->decrement('comments_count');
            }

            if ($parent instanceof Comment && $parent->replies_count > 0) {
                $parent->decrement('replies_count');
            }
        });
    }
}
