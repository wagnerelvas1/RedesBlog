<?php

use App\Models\Post;
use App\Models\User;
use App\Models\Vote;

it('records an upvote', function (): void {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('posts.vote', $post), ['value' => 1])
        ->assertSessionHasNoErrors();

    $post->refresh();

    expect($post->score)->toBe(1)
        ->and($post->upvotes_count)->toBe(1)
        ->and($post->downvotes_count)->toBe(0)
        ->and($post->hot_rank)->not->toBeNull();
});

it('is idempotent when the same value is sent twice', function (): void {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('posts.vote', $post), ['value' => 1]);
    $this->actingAs($user)->put(route('posts.vote', $post), ['value' => 1]);

    $post->refresh();

    expect($post->score)->toBe(1)
        ->and(Vote::query()->count())->toBe(1);
});

it('switches an upvote to a downvote', function (): void {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('posts.vote', $post), ['value' => 1]);
    $this->actingAs($user)->put(route('posts.vote', $post), ['value' => -1]);

    $post->refresh();

    expect($post->score)->toBe(-1)
        ->and($post->upvotes_count)->toBe(0)
        ->and($post->downvotes_count)->toBe(1)
        ->and(Vote::query()->count())->toBe(1);
});

it('clears a vote', function (): void {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('posts.vote', $post), ['value' => 1]);
    $this->actingAs($user)->delete(route('posts.unvote', $post));

    $post->refresh();

    expect($post->score)->toBe(0)
        ->and($post->upvotes_count)->toBe(0)
        ->and(Vote::query()->count())->toBe(0);
});

it('accumulates votes from several users', function (): void {
    $post = Post::factory()->create();

    foreach (User::factory()->count(3)->create() as $user) {
        $this->actingAs($user)->put(route('posts.vote', $post), ['value' => 1]);
    }

    expect($post->refresh()->score)->toBe(3);
});

it('rejects an invalid value', function (): void {
    $post = Post::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('posts.vote', $post), ['value' => 5])
        ->assertSessionHasErrors('value');
});

it('redirects guests', function (): void {
    $post = Post::factory()->create();

    $this->put(route('posts.vote', $post), ['value' => 1])->assertRedirect(route('login'));
});

it('reports the viewer vote back on the feed', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('posts.vote', $post), ['value' => 1]);

    $props = $this->actingAs($user)
        ->get(route('communities.show', $community))
        ->viewData('page')['props'];

    expect($props['posts'][0]['viewer_vote'])->toBe(1);
});

it('reorders the top sort after voting', function (): void {
    $community = communityOwnedBy();
    $quiet = Post::factory()->inCommunity($community)->create();
    $popular = Post::factory()->inCommunity($community)->create();

    foreach (User::factory()->count(3)->create() as $user) {
        $this->actingAs($user)->put(route('posts.vote', $popular), ['value' => 1]);
    }

    $ids = collect(
        $this->get(route('communities.show', [$community, 'sort' => 'top']))
            ->viewData('page')['props']['posts'],
    )->pluck('id')->all();

    expect($ids[0])->toBe($popular->id)->and($ids[1])->toBe($quiet->id);
});
