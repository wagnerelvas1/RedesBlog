<?php

use App\Models\Post;
use App\Models\User;

it('saves a post and lists it under /saved', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('posts.save', $post))
        ->assertSessionHasNoErrors();

    expect($post->savedByUsers()->whereKey($user->id)->exists())->toBeTrue();

    $this->actingAs($user)->get(route('posts.saved'))->assertOk();
});

it('works without community membership', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();
    $outsider = User::factory()->create();

    $this->actingAs($outsider)->put(route('posts.save', $post))->assertSessionHasNoErrors();

    expect($post->savedByUsers()->whereKey($outsider->id)->exists())->toBeTrue();
});

it('is idempotent', function (): void {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('posts.save', $post));
    $this->actingAs($user)->put(route('posts.save', $post));

    expect($post->savedByUsers()->count())->toBe(1);
});

it('unsaves a post', function (): void {
    $post = Post::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('posts.save', $post));
    $this->actingAs($user)->delete(route('posts.unsave', $post));

    expect($post->savedByUsers()->count())->toBe(0);
});

it('redirects guests', function (): void {
    $post = Post::factory()->create();

    $this->put(route('posts.save', $post))->assertRedirect(route('login'));
    $this->get(route('posts.saved'))->assertRedirect(route('login'));
});
