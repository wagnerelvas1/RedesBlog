<?php

use Illuminate\Support\Facades\Storage;

it('stores and reads a file on the s3 disk', function (): void {
    Storage::fake('s3');

    Storage::disk('s3')->put('smoke.txt', 'ok');

    expect(Storage::disk('s3')->get('smoke.txt'))->toBe('ok');
    Storage::disk('s3')->assertExists('smoke.txt');
});

it('uses the s3 disk as the application default', function (): void {
    expect(config('filesystems.default'))->toBe('s3');
});
