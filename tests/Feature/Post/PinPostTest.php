<?php

use App\Models\Post;
use App\Models\User;

it('lets an admin pin a post', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->create();

    $this->actingAs($creator)
        ->put(route('posts.pin', [$community, $post]))
        ->assertSessionHasNoErrors();

    $post->refresh();

    expect($post->is_pinned)->toBeTrue()
        ->and($post->pinned_by)->toBe($creator->id)
        ->and($post->pinned_at)->not->toBeNull();
});

it('lets an admin unpin a post', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $post = Post::factory()->inCommunity($community)->by($creator)->pinned()->create();

    $this->actingAs($creator)->delete(route('posts.unpin', [$community, $post]));

    $post->refresh();

    expect($post->is_pinned)->toBeFalse()->and($post->pinned_at)->toBeNull();
});

it('forbids a plain member from pinning', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($member)->create();

    $this->actingAs($member)
        ->put(route('posts.pin', [$community, $post]))
        ->assertForbidden();
});
