<?php

use App\Models\User;

it('joins a community and increments the member count', function (): void {
    $community = communityOwnedBy();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('communities.join', $community))
        ->assertRedirect();

    expect($community->refresh()->members_count)->toBe(2);
});

it('leaves a community and decrements the member count', function (): void {
    $community = communityOwnedBy();
    $user = joinCommunity($community, User::factory()->create());

    $this->actingAs($user)->delete(route('communities.leave', $community));

    expect($community->refresh()->members_count)->toBe(1);
});

it('prevents the creator from leaving', function (): void {
    $creator = User::factory()->create();
    $community = communityOwnedBy($creator);

    $this->actingAs($creator)
        ->delete(route('communities.leave', $community))
        ->assertSessionHas('error');

    expect($community->refresh()->members_count)->toBe(1);
});

it('prevents a banned user from rejoining', function (): void {
    $community = communityOwnedBy();
    $banned = banFromCommunity($community, User::factory()->create());

    $this->actingAs($banned)
        ->post(route('communities.join', $community))
        ->assertSessionHas('error');
});

it('redirects guests trying to join', function (): void {
    $community = communityOwnedBy();

    $this->post(route('communities.join', $community))->assertRedirect(route('login'));
});
