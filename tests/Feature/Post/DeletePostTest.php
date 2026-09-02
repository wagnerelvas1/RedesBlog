<?php

use App\Models\Post;
use App\Models\User;

it('lets the author delete their post', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->create();
    $community->increment('posts_count');

    $this->actingAs($author)
        ->delete(route('posts.destroy', [$community, $post]))
        ->assertRedirect(route('communities.show', $community));

    expect(Post::query()->whereKey($post->id)->exists())->toBeFalse()
        ->and(Post::withTrashed()->whereKey($post->id)->exists())->toBeTrue()
        ->and($community->refresh()->posts_count)->toBe(0);
});

it('lets a community admin delete another user post', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->create();

    $this->actingAs($creator)
        ->delete(route('posts.destroy', [$community, $post]))
        ->assertSessionHasNoErrors();

    expect(Post::query()->whereKey($post->id)->exists())->toBeFalse();
});

it('forbids a random member from deleting', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $other = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->create();

    $this->actingAs($other)
        ->delete(route('posts.destroy', [$community, $post]))
        ->assertForbidden();
});

it('returns 404 for a deleted post', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->create();

    $post->delete();

    $this->get(route('posts.show', [$community, $post]))->assertNotFound();
});
