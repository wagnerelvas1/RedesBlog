<?php

use App\Enums\CommunityRole;
use App\Models\Community;
use App\Models\User;

function confirmPassword(): void
{
    test()->post(route('password.confirm.store'), ['password' => 'password']);
}

it('requires a confirmed password', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);

    $this->actingAs($creator)
        ->delete(route('communities.destroy', $community), [
            'confirm_name' => $community->name,
        ])
        ->assertRedirect(route('password.confirm'));

    expect(Community::query()->whereKey($community->id)->exists())->toBeTrue();
});

it('lets the creator delete after confirming the password and name', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);

    $this->actingAs($creator);
    confirmPassword();

    $this->delete(route('communities.destroy', $community), [
        'confirm_name' => $community->name,
    ])->assertRedirect(route('communities.index'));

    expect(Community::query()->whereKey($community->id)->exists())->toBeFalse()
        ->and(Community::withTrashed()->whereKey($community->id)->exists())->toBeTrue();
});

it('rejects a mistyped community name', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);

    $this->actingAs($creator);
    confirmPassword();

    $this->delete(route('communities.destroy', $community), [
        'confirm_name' => 'wrong-name',
    ])->assertSessionHasErrors('confirm_name');

    expect(Community::query()->whereKey($community->id)->exists())->toBeTrue();
});

it('forbids a non-creator admin from deleting', function (): void {
    $community = communityOwnedBy();
    $admin = joinCommunity($community, User::factory()->create(), CommunityRole::Admin);

    $this->actingAs($admin);
    confirmPassword();

    $this->delete(route('communities.destroy', $community), [
        'confirm_name' => $community->name,
    ])->assertForbidden();
});
