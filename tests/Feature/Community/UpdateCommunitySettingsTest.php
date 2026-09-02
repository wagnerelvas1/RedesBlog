<?php

use App\Enums\CommunityRole;
use App\Models\User;

it('lets an admin update the settings', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);

    $this->actingAs($creator)
        ->patch(route('communities.settings.update', $community), [
            'title' => 'Renamed title',
            'description' => 'New description',
            'rules' => 'Be nice.',
        ])
        ->assertSessionHasNoErrors();

    $community->refresh();

    expect($community->title)->toBe('Renamed title')
        ->and($community->description)->toBe('New description')
        ->and($community->rules)->toBe('Be nice.');
});

it('ignores a name sent in the payload', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator, ['name' => 'original']);

    $this->actingAs($creator)->patch(
        route('communities.settings.update', $community),
        ['title' => 'Still fine', 'name' => 'hijacked'],
    );

    expect($community->refresh()->name)->toBe('original');
});

it('lets a promoted admin update the settings', function (): void {
    $community = communityOwnedBy();
    $admin = joinCommunity($community, User::factory()->create(), CommunityRole::Admin);

    $this->actingAs($admin)
        ->patch(route('communities.settings.update', $community), ['title' => 'By admin'])
        ->assertSessionHasNoErrors();

    expect($community->refresh()->title)->toBe('By admin');
});

it('forbids a plain member from updating the settings', function (): void {
    $community = communityOwnedBy();
    $member = joinCommunity($community, User::factory()->create());

    $this->actingAs($member)
        ->patch(route('communities.settings.update', $community), ['title' => 'Nope'])
        ->assertForbidden();
});

it('forbids a non-member from opening the settings', function (): void {
    $community = communityOwnedBy();

    $this->actingAs(User::factory()->create())
        ->get(route('communities.settings.edit', $community))
        ->assertForbidden();
});
