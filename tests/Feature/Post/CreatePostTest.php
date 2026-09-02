<?php

use App\Models\Attachment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets a member create a text-only post', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($member)
        ->post(route('posts.store', $community), [
            'title' => 'Hello world',
            'body' => 'Some body text.',
        ])
        ->assertSessionHasNoErrors();

    $post = Post::query()->firstOrFail();

    expect($post->title)->toBe('Hello world')
        ->and($post->comments_count)->toBe(0)
        ->and($post->score)->toBe(0)
        ->and($post->slug)->toContain('hello-world');
});

it('lets a member create a title-only post', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($member)
        ->post(route('posts.store', $community), ['title' => 'Just a title'])
        ->assertSessionHasNoErrors();

    expect(Post::query()->count())->toBe(1);
});

it('stores uploaded images with ordered positions', function (): void {
    Storage::fake('s3');
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($member)
        ->post(route('posts.store', $community), [
            'title' => 'With images',
            'images' => [
                UploadedFile::fake()->image('one.png', 400, 300),
                UploadedFile::fake()->image('two.png', 400, 300),
            ],
        ])
        ->assertSessionHasNoErrors();

    $post = Post::query()->firstOrFail();
    $attachments = $post->attachments()->get();

    expect($attachments)->toHaveCount(2)
        ->and($attachments->pluck('position')->all())->toBe([0, 1]);

    foreach ($attachments as $attachment) {
        Storage::disk('s3')->assertExists($attachment->path);
        expect($attachment->width)->toBe(400)->and($attachment->height)->toBe(300);
    }
});

it('increments the community post count', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($member)->post(route('posts.store', $community), ['title' => 'Counted']);

    expect($community->refresh()->posts_count)->toBe(1);
});

it('forbids a non-member from posting', function (): void {
    $community = communityOwnedBy();

    $this->actingAs(User::factory()->create())
        ->post(route('posts.store', $community), ['title' => 'Nope'])
        ->assertForbidden();
});

it('forbids a banned member from posting', function (): void {
    $community = communityOwnedBy();
    $banned = banFromCommunity($community, User::factory()->create());

    $this->actingAs($banned)
        ->post(route('posts.store', $community), ['title' => 'Nope'])
        ->assertForbidden();
});

it('redirects guests', function (): void {
    $community = communityOwnedBy();

    $this->post(route('posts.store', $community), ['title' => 'Nope'])
        ->assertRedirect(route('login'));
});

it('rejects more than ten images', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());

    $images = [];

    for ($i = 0; $i < 11; $i++) {
        $images[] = UploadedFile::fake()->image("img{$i}.png", 100, 100);
    }

    $this->actingAs($member)
        ->post(route('posts.store', $community), ['title' => 'Too many', 'images' => $images])
        ->assertSessionHasErrors('images');
});

it('rejects an oversized image', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($member)
        ->post(route('posts.store', $community), [
            'title' => 'Too big',
            'images' => [UploadedFile::fake()->image('big.png', 500, 500)->size(6000)],
        ])
        ->assertSessionHasErrors('images.0');
});

it('rejects a missing title', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($member)
        ->post(route('posts.store', $community), ['body' => 'No title'])
        ->assertSessionHasErrors('title');

    expect(Attachment::query()->count())->toBe(0);
});
