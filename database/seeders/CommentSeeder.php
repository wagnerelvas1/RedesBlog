<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Post;
use Database\Seeders\Support\ImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommentSeeder extends Seeder
{
    /** Nesting stops here so seeding stays fast. */
    private const MAX_DEPTH = 5;

    public function run(): void
    {
        Post::query()->with('community')->chunkById(50, function (Collection $posts): void {
            foreach ($posts as $post) {
                DB::transaction(function () use ($post): void {
                    $memberIds = $post->community?->members()
                        ->wherePivotNull('banned_at')
                        ->pluck('users.id');

                    if ($memberIds === null || $memberIds->isEmpty()) {
                        return;
                    }

                    $roots = random_int(0, 12);

                    for ($index = 0; $index < $roots; $index++) {
                        $this->makeComment($post, null, $memberIds, 0);
                    }

                    $post->comments_count = $post->allComments()->count();
                    $post->save();
                });
            }
        });
    }

    /**
     * @param  Collection<int, int>  $memberIds
     */
    private function makeComment(Post $post, ?Comment $parent, Collection $memberIds, int $depth): void
    {
        $base = $parent === null ? $post->created_at : $parent->created_at;
        $createdAt = ($base ?? now())->addMinutes(random_int(1, 2880));

        $comment = Comment::query()->create([
            'post_id' => $post->id,
            'user_id' => $memberIds->random(),
            'parent_id' => $parent?->id,
            'body' => fake()->paragraph(random_int(1, 3)),
            'depth' => $depth,
            'path' => $parent === null ? '/' : $parent->childPath(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        if (random_int(1, 100) <= 10) {
            $path = ImageGenerator::store('comments/'.$comment->id, 600, 400);

            Attachment::query()->create([
                'attachable_type' => $comment->getMorphClass(),
                'attachable_id' => $comment->id,
                'disk' => config('filesystems.default'),
                'path' => $path,
                'original_name' => 'comment.png',
                'mime_type' => 'image/png',
                'size' => 20_000,
                'width' => 600,
                'height' => 400,
                'position' => 0,
            ]);
        }

        if ($parent !== null) {
            $parent->increment('replies_count');
        }

        if ($depth >= self::MAX_DEPTH) {
            return;
        }

        // Reply probability decays with depth, producing realistic threads.
        $replies = random_int(0, max(0, 3 - intdiv($depth, 2)));

        for ($index = 0; $index < $replies; $index++) {
            if (random_int(1, 100) > 70 - $depth * 10) {
                continue;
            }

            $this->makeComment($post, $comment, $memberIds, $depth + 1);
        }
    }
}
