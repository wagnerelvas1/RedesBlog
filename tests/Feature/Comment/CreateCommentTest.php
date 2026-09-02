<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets a member comment on a post', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();

    $this->actingAs($member)
        ->post(route('comments.store', [$community, $post]), ['body' => 'Nice post!'])
        ->assertSessionHasNoErrors();

    $comment = Comment::query()->firstOrFail();

    expect($comment->body)->toBe('Nice post!')
        ->and($comment->depth)->toBe(0)
        ->and($comment->path)->toBe('/')
        ->and($post->refresh()->comments_count)->toBe(1);
});

it('nests a reply with the right depth and path', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();
    $parent = Comment::factory()->onPost($post)->by($member)->create();

    $this->actingAs($member)->post(route('comments.store', [$community, $post]), [
        'body' => 'A reply',
        'parent_id' => $parent->id,
    ])->assertSessionHasNoErrors();

    $reply = Comment::query()->where('parent_id', $parent->id)->firstOrFail();

    expect($reply->depth)->toBe(1)
        ->and($reply->path)->toBe('/'.$parent->id.'/')
        ->and($parent->refresh()->replies_count)->toBe(1)
        ->and($post->refresh()->comments_count)->toBe(1);
});

it('accepts an image-only comment', function (): void {
    Storage::fake('s3');
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();

    $this->actingAs($member)->post(route('comments.store', [$community, $post]), [
        'image' => UploadedFile::fake()->image('pic.png', 300, 200),
    ])->assertSessionHasNoErrors();

    $comment = Comment::query()->firstOrFail();

    expect($comment->attachment)->not->toBeNull();
    Storage::disk('s3')->assertExists($comment->attachment->path);
});

it('rejects a comment with neither body nor image', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();

    $this->actingAs($member)
        ->post(route('comments.store', [$community, $post]), [])
        ->assertSessionHasErrors('body');
});

it('rejects a parent from a different post', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();
    $otherPost = Post::factory()->inCommunity($community)->create();
    $foreignParent = Comment::factory()->onPost($otherPost)->create();

    $this->actingAs($member)->post(route('comments.store', [$community, $post]), [
        'body' => 'Nope',
        'parent_id' => $foreignParent->id,
    ])->assertSessionHasErrors('parent_id');
});

it('rejects a reply to a deleted parent', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();
    $parent = Comment::factory()->onPost($post)->deleted()->create();

    $this->actingAs($member)->post(route('comments.store', [$community, $post]), [
        'body' => 'Nope',
        'parent_id' => $parent->id,
    ])->assertSessionHasErrors('parent_id');
});

it('forbids a non-member from commenting', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();

    $this->actingAs(User::factory()->create())
        ->post(route('comments.store', [$community, $post]), ['body' => 'Nope'])
        ->assertForbidden();
});

it('forbids a banned member from commenting', function (): void {
    $community = communityOwnedBy();
    $banned = banFromCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();

    $this->actingAs($banned)
        ->post(route('comments.store', [$community, $post]), ['body' => 'Nope'])
        ->assertForbidden();
});

it('redirects guests', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();

    $this->post(route('comments.store', [$community, $post]), ['body' => 'Nope'])
        ->assertRedirect(route('login'));
});
