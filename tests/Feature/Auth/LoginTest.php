<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

it('renders the login screen', function (): void {
    $this->get(route('login'))->assertOk();
});

it('authenticates with valid credentials', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});

it('authenticates case-insensitively on the email', function (): void {
    $user = User::factory()->create(['email' => 'ada@example.com']);

    $this->post(route('login.store'), [
        'email' => 'ADA@EXAMPLE.COM',
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong password', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('sets the remember cookie when asked', function (): void {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'remember' => true,
    ])->assertCookie(Auth::guard('web')->getRecallerName());
});

it('throttles repeated failed attempts', function (): void {
    $user = User::factory()->create();

    foreach (range(1, 5) as $ignored) {
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();

    expect(RateLimiter::tooManyAttempts(
        Str::transliterate(Str::lower($user->email).'|127.0.0.1'),
        5,
    ))->toBeTrue();
});

it('logs the user out', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});
