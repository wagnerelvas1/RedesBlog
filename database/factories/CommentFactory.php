<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'depth' => 0,
            'path' => '/',
            'score' => 0,
            'upvotes_count' => 0,
            'downvotes_count' => 0,
            'replies_count' => 0,
        ];
    }

    public function onPost(Post $post): static
    {
        return $this->state(fn (array $attributes): array => [
            'post_id' => $post->id,
        ]);
    }

    public function by(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Places the comment under `$parent`, deriving depth and path the same way
     * the repository does.
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'post_id' => $parent->post_id,
            'parent_id' => $parent->id,
            'depth' => $parent->depth + 1,
            'path' => $parent->childPath(),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now(),
        ]);
    }

    public function withImage(): static
    {
        return $this->afterCreating(function (Comment $comment): void {
            Attachment::factory()->forImage()->create([
                'attachable_type' => $comment->getMorphClass(),
                'attachable_id' => $comment->id,
            ]);
        });
    }
}
