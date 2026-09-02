<?php

namespace App\Services;

use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use App\Repositories\PostRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Business rules for creating, editing, pinning and saving posts.
 */
final class PostService
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly AttachmentService $attachments,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $images
     */
    public function create(Community $community, User $author, array $data, array $images = []): Post
    {
        return DB::transaction(function () use ($community, $author, $data, $images): Post {
            $post = $this->posts->create($community, $author, [
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
            ]);

            $this->attachments->attachMany($post, $images, 'posts/'.$post->id);

            $community->increment('posts_count');

            return $post->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $keepImageIds
     * @param  array<int, UploadedFile>  $newImages
     */
    public function update(Post $post, array $data, array $keepImageIds = [], array $newImages = []): Post
    {
        return DB::transaction(function () use ($post, $data, $keepImageIds, $newImages): Post {
            $this->posts->update($post, [
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
            ]);

            $this->attachments->sync($post, $keepImageIds, $newImages, 'posts/'.$post->id);

            return $post->refresh();
        });
    }

    /**
     * Soft delete; the blobs survive until a purge job runs.
     */
    public function delete(Post $post): void
    {
        DB::transaction(function () use ($post): void {
            $this->posts->delete($post);

            $post->community()->first()?->decrement('posts_count');
        });
    }

    public function pin(Post $post, User $admin): void
    {
        $this->posts->setPinned($post, $admin);
    }

    public function unpin(Post $post): void
    {
        $this->posts->setPinned($post, null);
    }

    /**
     * Idempotent: saving an already-saved post is a no-op.
     */
    public function save(User $user, Post $post): void
    {
        $this->posts->save($user, $post);
    }

    /**
     * Idempotent: unsaving a post that is not saved is a no-op.
     */
    public function unsave(User $user, Post $post): void
    {
        $this->posts->unsave($user, $post);
    }

    /**
     * @return bool the new saved state
     */
    public function toggleSave(User $user, Post $post): bool
    {
        if ($this->posts->isSaved($post, $user)) {
            $this->posts->unsave($user, $post);

            return false;
        }

        $this->posts->save($user, $post);

        return true;
    }
}
