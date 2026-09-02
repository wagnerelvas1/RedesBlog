<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('requires a confirmed password', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'))
        ->assertRedirect(route('password.confirm'));

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
});

it('deletes the account once the password is confirmed', function (): void {
    Storage::fake('s3');
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('password.confirm.store'), [
        'password' => 'password',
    ]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'))
        ->assertRedirect(route('home'));

    expect(User::query()->whereKey($user->id)->exists())->toBeFalse();
});

it('blocks deletion while the user still owns a community', function (): void {
    $user = User::factory()->create();
    communityOwnedBy($user, ['name' => 'ownedone']);

    $this->actingAs($user)->post(route('password.confirm.store'), [
        'password' => 'password',
    ]);

    $this->actingAs($user)
        ->delete(route('profile.destroy'))
        ->assertSessionHasErrors('password');

    expect(User::query()->whereKey($user->id)->exists())->toBeTrue();
});
