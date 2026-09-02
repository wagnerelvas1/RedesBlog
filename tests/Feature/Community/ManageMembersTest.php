<?php

use App\Enums\CommunityRole;
use App\Models\User;

it('lets the creator promote a member to admin', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($creator)
        ->patch(route('communities.members.update', [$community, $member]), [
            'role' => 'admin',
        ])
        ->assertSessionHasNoErrors();

    $pivot = $community->members()->whereKey($member->id)->firstOrFail()->pivot;

    expect($pivot->role)->toBe(CommunityRole::Admin);
});

it('lets the creator demote an admin', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $admin = joinCommunity($community, User::factory()->create(), CommunityRole::Admin);

    $this->actingAs($creator)->patch(
        route('communities.members.update', [$community, $admin]),
        ['role' => 'member'],
    );

    $pivot = $community->members()->whereKey($admin->id)->firstOrFail()->pivot;

    expect($pivot->role)->toBe(CommunityRole::Member);
});

it('forbids a non-creator admin from changing roles', function (): void {
    $community = communityOwnedBy();
    $admin = joinCommunity($community, User::factory()->create(), CommunityRole::Admin);
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($admin)
        ->patch(route('communities.members.update', [$community, $member]), [
            'role' => 'admin',
        ])
        ->assertForbidden();
});

it('never lets the creator be demoted', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);

    $this->actingAs($creator)
        ->patch(route('communities.members.update', [$community, $creator]), [
            'role' => 'member',
        ])
        ->assertSessionHas('error');

    $pivot = $community->members()->whereKey($creator->id)->firstOrFail()->pivot;

    expect($pivot->role)->toBe(CommunityRole::Admin)
        ->and($pivot->is_creator)->toBeTrue();
});

it('lets an admin ban a member, blocking a rejoin', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($creator)->patch(
        route('communities.members.update', [$community, $member]),
        ['banned' => true],
    );

    $pivot = $community->members()->whereKey($member->id)->firstOrFail()->pivot;

    expect($pivot->banned_at)->not->toBeNull()
        ->and($community->refresh()->members_count)->toBe(1);

    $this->actingAs($member)
        ->post(route('communities.join', $community))
        ->assertSessionHas('error');
});

it('lets an admin unban a member', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $member = banFromCommunity($community, User::factory()->create());

    $this->actingAs($creator)->patch(
        route('communities.members.update', [$community, $member]),
        ['banned' => false],
    );

    $pivot = $community->members()->whereKey($member->id)->firstOrFail()->pivot;

    expect($pivot->banned_at)->toBeNull();
});

it('never lets the creator be banned', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $admin = joinCommunity($community, User::factory()->create(), CommunityRole::Admin);

    $this->actingAs($admin)
        ->patch(route('communities.members.update', [$community, $creator]), [
            'banned' => true,
        ])
        ->assertSessionHas('error');

    $pivot = $community->members()->whereKey($creator->id)->firstOrFail()->pivot;

    expect($pivot->banned_at)->toBeNull();
});

it('prevents an admin from banning themselves', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $admin = joinCommunity($community, User::factory()->create(), CommunityRole::Admin);

    $this->actingAs($admin)
        ->patch(route('communities.members.update', [$community, $admin]), [
            'banned' => true,
        ])
        ->assertSessionHas('error');
});

it('lets an admin remove a member', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($creator)
        ->delete(route('communities.members.destroy', [$community, $member]))
        ->assertSessionHasNoErrors();

    expect($community->members()->whereKey($member->id)->exists())->toBeFalse()
        ->and($community->refresh()->members_count)->toBe(1);
});

it('never lets the creator be removed', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $admin = joinCommunity($community, User::factory()->create(), CommunityRole::Admin);

    $this->actingAs($admin)
        ->delete(route('communities.members.destroy', [$community, $creator]))
        ->assertSessionHas('error');

    expect($community->members()->whereKey($creator->id)->exists())->toBeTrue();
});

it('shows the members screen to admins only', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($creator)->get(route('communities.members.index', $community))->assertOk();
    $this->actingAs($member)->get(route('communities.members.index', $community))->assertForbidden();
});
