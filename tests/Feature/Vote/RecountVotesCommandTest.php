<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\UniqueConstraintViolationException;

it('rebuilds counters that drifted out of step', function (): void {
    $post = Post::factory()->create();
    $comment = Comment::factory()->onPost($post)->create();
    $users = User::factory()->count(3)->create();

    foreach ($users as $user) {
        Vote::factory()->by($user)->forVotable($post)->up()->create();
        Vote::factory()->by($user)->forVotable($comment)->up()->create();
    }

    // Corrupt the cached values.
    $post->forceFill(['score' => 99, 'upvotes_count' => 99])->save();
    $comment->forceFill(['score' => -42, 'upvotes_count' => 0])->save();

    $this->artisan('votes:recount')->assertSuccessful();

    $post->refresh();
    $comment->refresh();

    expect($post->score)->toBe(3)
        ->and($post->upvotes_count)->toBe(3)
        ->and($post->hot_rank)->not->toBeNull()
        ->and($comment->score)->toBe(3)
        ->and($comment->upvotes_count)->toBe(3)
        ->and($comment->best_rank)->toBeGreaterThan(0.0);
});

it('prevents a duplicate vote row per user and item', function (): void {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    Vote::factory()->by($user)->forVotable($post)->up()->create();

    expect(fn () => Vote::factory()->by($user)->forVotable($post)->down()->create())
        ->toThrow(UniqueConstraintViolationException::class);
});
