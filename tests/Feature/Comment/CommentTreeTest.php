<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('renders nested replies under their parent', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();
    $root = Comment::factory()->onPost($post)->create(['replies_count' => 1]);
    $reply = Comment::factory()->onPost($post)->replyTo($root)->create();

    $response = $this->get(route('posts.show', [$community, $post]));
    $comments = $response->viewData('page')['props']['comments'];

    expect($comments)->toHaveCount(1)
        ->and($comments[0]['id'])->toBe($root->id)
        ->and($comments[0]['replies'])->toHaveCount(1)
        ->and($comments[0]['replies'][0]['id'])->toBe($reply->id);
});

it('flags a deleted comment while keeping its children', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();
    $root = Comment::factory()->onPost($post)->deleted()->create(['replies_count' => 1]);
    Comment::factory()->onPost($post)->replyTo($root)->create();

    $comments = $this->get(route('posts.show', [$community, $post]))
        ->viewData('page')['props']['comments'];

    expect($comments[0]['is_deleted'])->toBeTrue()
        ->and($comments[0]['body'])->toBe('')
        ->and($comments[0]['author'])->toBeNull()
        ->and($comments[0]['replies'])->toHaveCount(1);
});

it('caps the replies loaded per node and flags the overflow', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();
    $root = Comment::factory()->onPost($post)->create(['replies_count' => 8]);

    Comment::factory()->count(8)->onPost($post)->replyTo($root)->create();

    $comments = $this->get(route('posts.show', [$community, $post]))
        ->viewData('page')['props']['comments'];

    expect($comments[0]['replies'])->toHaveCount(5)
        ->and($comments[0]['has_more_replies'])->toBeTrue();
});

it('orders comments by the requested sort', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();

    $older = Comment::factory()->onPost($post)->create(['created_at' => now()->subDay()]);
    $newer = Comment::factory()->onPost($post)->create(['created_at' => now()]);

    $ids = collect(
        $this->get(route('posts.show', [$community, $post, 'sort' => 'new']))
            ->viewData('page')['props']['comments'],
    )->pluck('id')->all();

    expect($ids)->toBe([$newer->id, $older->id]);

    $ids = collect(
        $this->get(route('posts.show', [$community, $post, 'sort' => 'old']))
            ->viewData('page')['props']['comments'],
    )->pluck('id')->all();

    expect($ids)->toBe([$older->id, $newer->id]);
});

it('rejects an unknown comment sort', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();

    $this->get(route('posts.show', [$community, $post, 'sort' => 'bogus']))
        ->assertSessionHasErrors('sort');
});

it('paginates a subtree through the index endpoint', function (): void {
    $community = communityOwnedBy();
    $post = Post::factory()->inCommunity($community)->create();
    $root = Comment::factory()->onPost($post)->create(['replies_count' => 3]);
    Comment::factory()->count(3)->onPost($post)->replyTo($root)->create();

    $this->getJson(route('comments.index', [$community, $post, 'parent_id' => $root->id]))
        ->assertOk()
        ->assertJsonCount(3, 'comments');
});

it('renders a deep tree with a bounded query count', function (): void {
    $community = communityOwnedBy();
    $viewer = joinCommunity($community, User::factory()->create());
    $post = Post::factory()->inCommunity($community)->create();

    // Four levels, several nodes each.
    foreach (range(1, 4) as $ignored) {
        $root = Comment::factory()->onPost($post)->create(['replies_count' => 2]);
        $children = Comment::factory()->count(2)->onPost($post)->replyTo($root)->create();

        foreach ($children as $child) {
            $child->update(['replies_count' => 2]);
            Comment::factory()->count(2)->onPost($post)->replyTo($child)->create();
        }
    }

    DB::enableQueryLog();
    $this->actingAs($viewer)->get(route('posts.show', [$community, $post]))->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // One query per tree level, not per node.
    expect($queries)->toBeLessThan(20);
});
