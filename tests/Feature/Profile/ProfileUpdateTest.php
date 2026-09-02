<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('renders the profile settings screen', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('profile.edit'))
        ->assertOk();
});

it('updates name, username and bio', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Ada Lovelace',
            'username' => 'ada_l',
            'bio' => 'Mathematician.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('Ada Lovelace')
        ->and($user->username)->toBe('ada_l')
        ->and($user->bio)->toBe('Mathematician.');
});

it('lets a user keep their own username', function (): void {
    $user = User::factory()->create(['username' => 'ada']);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => 'ada',
        ])
        ->assertSessionHasNoErrors();
});

it('rejects a username taken by someone else', function (): void {
    User::factory()->create(['username' => 'taken']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => 'TAKEN',
        ])
        ->assertSessionHasErrors('username');
});

it('stores an uploaded avatar on the s3 disk', function (): void {
    Storage::fake('s3');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'avatar' => UploadedFile::fake()->image('me.png', 400, 400),
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->avatar_path)->not->toBeNull();
    Storage::disk('s3')->assertExists($user->avatar_path);
});

it('removes the avatar and deletes the blob', function (): void {
    Storage::fake('s3');
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'username' => $user->username,
        'avatar' => UploadedFile::fake()->image('me.png', 400, 400),
    ]);

    $path = $user->refresh()->avatar_path;

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'username' => $user->username,
        'remove_avatar' => true,
    ]);

    expect($user->refresh()->avatar_path)->toBeNull();
    Storage::disk('s3')->assertMissing($path);
});

it('rejects an avatar that is not an image', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'avatar' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors('avatar');
});

it('shows a public profile by username, case-insensitively', function (): void {
    $user = User::factory()->create(['username' => 'ada']);

    $this->get(route('profile.show', 'ADA'))->assertOk();
});
