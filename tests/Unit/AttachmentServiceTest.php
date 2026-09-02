<?php

use App\Models\Post;
use App\Repositories\AttachmentRepository;
use App\Services\AttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('s3');
    $this->service = new AttachmentService(new AttachmentRepository);
});

it('attaches many files in order', function (): void {
    $post = Post::factory()->create();

    $this->service->attachMany($post, [
        UploadedFile::fake()->image('a.png', 100, 50),
        UploadedFile::fake()->image('b.png', 100, 50),
    ], 'posts/'.$post->id);

    $attachments = $post->attachments()->get();

    expect($attachments)->toHaveCount(2)
        ->and($attachments->pluck('position')->all())->toBe([0, 1])
        ->and($attachments->first()->width)->toBe(100)
        ->and($attachments->first()->height)->toBe(50);

    foreach ($attachments as $attachment) {
        Storage::disk('s3')->assertExists($attachment->path);
    }
});

it('attaches a single file', function (): void {
    $post = Post::factory()->create();

    $attachment = $this->service->attachOne(
        $post,
        UploadedFile::fake()->image('one.png', 20, 20),
        'posts/'.$post->id,
    );

    expect($attachment->original_name)->toBe('one.png')
        ->and($attachment->disk)->toBe('s3');

    Storage::disk('s3')->assertExists($attachment->path);
});

it('syncs by dropping removed files and appending new ones', function (): void {
    $post = Post::factory()->create();

    $created = $this->service->attachMany($post, [
        UploadedFile::fake()->image('a.png', 10, 10),
        UploadedFile::fake()->image('b.png', 10, 10),
    ], 'posts/'.$post->id);

    $keep = $created[0];
    $dropped = $created[1];

    $this->service->sync(
        $post,
        [$keep->id],
        [UploadedFile::fake()->image('c.png', 10, 10)],
        'posts/'.$post->id,
    );

    $paths = $post->attachments()->pluck('path');

    expect($post->attachments()->count())->toBe(2)
        ->and($paths)->toContain($keep->path);

    Storage::disk('s3')->assertMissing($dropped->path);
});

it('renumbers positions after a removal', function (): void {
    $post = Post::factory()->create();

    $created = $this->service->attachMany($post, [
        UploadedFile::fake()->image('a.png', 10, 10),
        UploadedFile::fake()->image('b.png', 10, 10),
        UploadedFile::fake()->image('c.png', 10, 10),
    ], 'posts/'.$post->id);

    $this->service->sync($post, [$created[1]->id, $created[2]->id], [], 'posts/'.$post->id);

    expect($post->attachments()->pluck('position')->all())->toBe([0, 1]);
});

it('detaches everything and deletes the blobs', function (): void {
    $post = Post::factory()->create();

    $created = $this->service->attachMany($post, [
        UploadedFile::fake()->image('a.png', 10, 10),
    ], 'posts/'.$post->id);

    $this->service->detachAll($post);

    expect($post->attachments()->count())->toBe(0);
    Storage::disk('s3')->assertMissing($created[0]->path);
});

it('stores and removes a standalone file', function (): void {
    $path = $this->service->storeStandalone(
        UploadedFile::fake()->image('avatar.png', 64, 64),
        'avatars',
    );

    Storage::disk('s3')->assertExists($path);

    $this->service->deleteStandalone($path);

    Storage::disk('s3')->assertMissing($path);
});
