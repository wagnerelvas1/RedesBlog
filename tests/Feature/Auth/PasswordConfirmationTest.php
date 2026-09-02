<?php

use App\Models\User;

it('renders the confirm-password screen', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('password.confirm'))
        ->assertOk();
});

it('confirms with the correct password', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(session()->has('auth.password_confirmed_at'))->toBeTrue();
});

it('rejects the wrong password', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('password.confirm.store'), ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect(session()->has('auth.password_confirmed_at'))->toBeFalse();
});

it('requires authentication', function (): void {
    $this->get(route('password.confirm'))->assertRedirect(route('login'));
});
