<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

it('records an upvote and computes the best rank', function (): void {
    $comment = Comment::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('comments.vote', $comment), ['value' => 1])
        ->assertSessionHasNoErrors();

    $comment->refresh();

    expect($comment->score)->toBe(1)
        ->and($comment->upvotes_count)->toBe(1)
        ->and($comment->best_rank)->toBeGreaterThan(0.0);
});

it('switches and clears a comment vote', function (): void {
    $comment = Comment::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('comments.vote', $comment), ['value' => 1]);
    $this->actingAs($user)->put(route('comments.vote', $comment), ['value' => -1]);

    expect($comment->refresh()->score)->toBe(-1);

    $this->actingAs($user)->delete(route('comments.unvote', $comment));

    expect($comment->refresh()->score)->toBe(0);
});

it('orders the best sort by the wilson rank', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();

    $weak = Comment::factory()->onPost($post)->create();
    $strong = Comment::factory()->onPost($post)->create();

    foreach (User::factory()->count(4)->create() as $user) {
        $this->actingAs($user)->put(route('comments.vote', $strong), ['value' => 1]);
    }

    $this->actingAs(User::factory()->create())
        ->put(route('comments.vote', $weak), ['value' => 1]);

    $ids = collect(
        $this->get(route('posts.show', [$community, $post, 'sort' => 'best']))
            ->viewData('page')['props']['comments'],
    )->pluck('id')->all();

    expect($ids[0])->toBe($strong->id);
});

it('redirects guests', function (): void {
    $comment = Comment::factory()->create();

    $this->put(route('comments.vote', $comment), ['value' => 1])
        ->assertRedirect(route('login'));
});
