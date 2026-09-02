<?php

use App\Enums\CommunityRole;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets the author edit and marks the post as edited', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->create();

    $this->actingAs($author)
        ->patch(route('posts.update', [$community, $post]), [
            'title' => 'Updated title',
            'body' => 'Updated body',
        ])
        ->assertSessionHasNoErrors();

    $post->refresh();

    expect($post->title)->toBe('Updated title')
        ->and($post->edited_at)->not->toBeNull();
});

it('forbids a community admin from editing another user post', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->create();

    $this->actingAs($creator)
        ->patch(route('posts.update', [$community, $post]), ['title' => 'Hijack'])
        ->assertForbidden();
});

it('forbids a random member from editing', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $other = joinCommunity($community, User::factory()->create(), CommunityRole::Member);
    $post = Post::factory()->inCommunity($community)->by($author)->create();

    $this->actingAs($other)
        ->patch(route('posts.update', [$community, $post]), ['title' => 'Nope'])
        ->assertForbidden();
});

it('removes images left out of existing_images', function (): void {
    Storage::fake('s3');
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->withImages(2)->create();

    $keep = $post->attachments()->first();

    $this->actingAs($author)
        ->patch(route('posts.update', [$community, $post]), [
            'title' => $post->title,
            'existing_images' => [$keep->id],
        ])
        ->assertSessionHasNoErrors();

    expect($post->attachments()->count())->toBe(1)
        ->and($post->attachments()->first()->id)->toBe($keep->id);
});

it('appends newly uploaded images', function (): void {
    Storage::fake('s3');
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->withImages(1)->create();

    $keep = $post->attachments()->pluck('id')->all();

    $this->actingAs($author)
        ->patch(route('posts.update', [$community, $post]), [
            'title' => $post->title,
            'existing_images' => $keep,
            'images' => [UploadedFile::fake()->image('new.png', 300, 200)],
        ])
        ->assertSessionHasNoErrors();

    expect($post->attachments()->count())->toBe(2);
});

it('rejects an existing_images id from another post', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->by($author)->create();
    $other = Post::factory()->inCommunity($community)->by($author)->withImages(1)->create();

    $this->actingAs($author)
        ->patch(route('posts.update', [$community, $post]), [
            'title' => $post->title,
            'existing_images' => [$other->attachments()->first()->id],
        ])
        ->assertSessionHasErrors('existing_images');
});
