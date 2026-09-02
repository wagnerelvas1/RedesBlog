<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Community;
use App\Models\Post;
use Database\Seeders\Support\ImageGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Community::query()->get() as $community) {
            DB::transaction(function () use ($community): void {
                $memberIds = $community->members()
                    ->wherePivotNull('banned_at')
                    ->pluck('users.id');

                if ($memberIds->isEmpty()) {
                    return;
                }

                $count = random_int(8, 30);
                $pinnedQuota = random_int(1, 2);

                for ($index = 0; $index < $count; $index++) {
                    $title = fake()->sentence(random_int(5, 12));
                    $createdAt = now()->subDays(random_int(0, 60))->subMinutes(random_int(0, 1440));

                    $post = Post::query()->create([
                        'community_id' => $community->id,
                        'user_id' => $memberIds->random(),
                        'title' => $title,
                        'body' => random_int(1, 10) > 2 ? fake()->paragraphs(random_int(1, 4), true) : null,
                        'slug' => Str::slug(Str::limit($title, 80, '')),
                        'is_pinned' => $index < $pinnedQuota,
                        'pinned_at' => $index < $pinnedQuota ? $createdAt : null,
                        'pinned_by' => $index < $pinnedQuota ? $community->created_by : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $post->slug = $post->id.'-'.$post->slug;
                    $post->save();

                    // About a third of the posts carry an image gallery.
                    if (random_int(1, 100) <= 35) {
                        $this->attachImages($post, random_int(1, 4));
                    }
                }

                $community->posts_count = $community->posts()->count();
                $community->save();
            });
        }
    }

    private function attachImages(Post $post, int $count): void
    {
        for ($position = 0; $position < $count; $position++) {
            $path = ImageGenerator::store('posts/'.$post->id, 1200, 800);

            Attachment::query()->create([
                'attachable_type' => $post->getMorphClass(),
                'attachable_id' => $post->id,
                'disk' => config('filesystems.default'),
                'path' => $path,
                'original_name' => 'image-'.($position + 1).'.png',
                'mime_type' => 'image/png',
                'size' => 40_000,
                'width' => 1200,
                'height' => 800,
                'position' => $position,
            ]);
        }
    }
}
