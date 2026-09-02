<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets the author edit and marks it edited', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();
    $comment = Comment::factory()->onPost($post)->by($author)->create();

    $this->actingAs($author)
        ->patch(route('comments.update', [$community, $post, $comment]), [
            'body' => 'Edited body',
        ])
        ->assertSessionHasNoErrors();

    $comment->refresh();

    expect($comment->body)->toBe('Edited body')
        ->and($comment->edited_at)->not->toBeNull();
});

it('swaps the attached image', function (): void {
    Storage::fake('s3');
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();
    $comment = Comment::factory()->onPost($post)->by($author)->create();

    $this->actingAs($author)->patch(
        route('comments.update', [$community, $post, $comment]),
        ['body' => 'With image', 'image' => UploadedFile::fake()->image('a.png', 200, 200)],
    )->assertSessionHasNoErrors();

    expect($comment->refresh()->attachment)->not->toBeNull();
});

it('forbids a community admin from editing another user comment', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();
    $comment = Comment::factory()->onPost($post)->by($author)->create();

    $this->actingAs($creator)
        ->patch(route('comments.update', [$community, $post, $comment]), ['body' => 'Hijack'])
        ->assertForbidden();
});

it('lets the author delete a leaf comment', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create(['comments_count' => 1]);
    $comment = Comment::factory()->onPost($post)->by($author)->create();

    $this->actingAs($author)
        ->delete(route('comments.destroy', [$community, $post, $comment]))
        ->assertSessionHasNoErrors();

    expect(Comment::query()->whereKey($comment->id)->exists())->toBeFalse()
        ->and($post->refresh()->comments_count)->toBe(0);
});

it('keeps a deleted comment that still has replies', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create(['comments_count' => 2]);
    $parent = Comment::factory()->onPost($post)->by($author)->create(['replies_count' => 1]);
    $reply = Comment::factory()->onPost($post)->replyTo($parent)->create();

    $this->actingAs($author)->delete(
        route('comments.destroy', [$community, $post, $parent]),
    );

    expect(Comment::withTrashed()->whereKey($parent->id)->exists())->toBeTrue()
        ->and(Comment::query()->whereKey($reply->id)->exists())->toBeTrue()
        ->and($post->refresh()->comments_count)->toBe(1);
});

it('lets a community admin delete another user comment', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $author = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create(['comments_count' => 1]);
    $comment = Comment::factory()->onPost($post)->by($author)->create();

    $this->actingAs($creator)
        ->delete(route('comments.destroy', [$community, $post, $comment]))
        ->assertSessionHasNoErrors();

    expect(Comment::query()->whereKey($comment->id)->exists())->toBeFalse();
});

it('forbids a random member from deleting', function (): void {
    $community = communityOwnedBy();
    $author = joinCommunity($community, User::factory()->create());
    $other = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();
    $comment = Comment::factory()->onPost($post)->by($author)->create();

    $this->actingAs($other)
        ->delete(route('comments.destroy', [$community, $post, $comment]))
        ->assertForbidden();
});
