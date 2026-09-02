<?php

use App\Models\User;

it('renders the registration screen for guests', function (): void {
    $this->get(route('register'))->assertOk();
});

it('redirects authenticated users away from registration', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('register'))
        ->assertRedirect();
});

it('registers a user and logs them in', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Ada Lovelace',
        'username' => 'ada',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();

    $user = User::query()->where('username', 'ada')->firstOrFail();
    expect($user->email)->toBe('ada@example.com');
});

it('rejects a duplicate email regardless of casing', function (): void {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->post(route('register.store'), [
        'name' => 'Ada',
        'username' => 'ada2',
        'email' => 'ADA@EXAMPLE.COM',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects a duplicate username regardless of casing', function (): void {
    User::factory()->create(['username' => 'ada']);

    $this->post(route('register.store'), [
        'name' => 'Ada',
        'username' => 'ADA',
        'email' => 'other@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('username');
});

it('rejects a username with invalid characters', function (): void {
    $this->post(route('register.store'), [
        'name' => 'Ada',
        'username' => 'ada lovelace!',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertSessionHasErrors('username');
});

it('rejects a password that is not confirmed', function (): void {
    $this->post(route('register.store'), [
        'name' => 'Ada',
        'username' => 'ada',
        'email' => 'ada@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors('password');
});
