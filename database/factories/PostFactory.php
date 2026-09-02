<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(8);

        return [
            'community_id' => Community::factory(),
            'user_id' => User::factory(),
            'title' => $title,
            'body' => fake()->paragraphs(2, true),
            'slug' => Str::slug(Str::limit($title, 80, '')),
            'is_pinned' => false,
            'score' => 0,
            'upvotes_count' => 0,
            'downvotes_count' => 0,
            'comments_count' => 0,
        ];
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_pinned' => true,
            'pinned_at' => now(),
        ]);
    }

    public function inCommunity(Community $community): static
    {
        return $this->state(fn (array $attributes): array => [
            'community_id' => $community->id,
        ]);
    }

    public function by(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function old(): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_at' => now()->subMonths(3),
            'updated_at' => now()->subMonths(3),
        ]);
    }

    /**
     * Attaches `$count` image rows once the post exists.
     */
    public function withImages(int $count = 2): static
    {
        return $this->afterCreating(function (Post $post) use ($count): void {
            for ($position = 0; $position < $count; $position++) {
                Attachment::factory()
                    ->forImage()
                    ->atPosition($position)
                    ->create([
                        'attachable_type' => $post->getMorphClass(),
                        'attachable_id' => $post->id,
                    ]);
            }
        });
    }
}
