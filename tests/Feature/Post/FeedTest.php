<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('places pinned posts first in the community feed', function (): void {
    $community = communityOwnedBy();
    Post::factory()->count(3)->inCommunity($community)->create();
    $pinned = Post::factory()->inCommunity($community)->pinned()->create();

    $response = $this->get(route('communities.show', [$community, 'sort' => 'new']));

    $ids = collect($response->viewData('page')['props']['posts'])->pluck('id');

    expect($ids->first())->toBe($pinned->id);
});

it('orders the new sort by recency', function (): void {
    $community = communityOwnedBy();
    $older = Post::factory()->inCommunity($community)->create(['created_at' => now()->subDays(3)]);
    $newer = Post::factory()->inCommunity($community)->create(['created_at' => now()]);

    $response = $this->get(route('communities.show', [$community, 'sort' => 'new']));
    $ids = collect($response->viewData('page')['props']['posts'])->pluck('id')->all();

    expect(array_search($newer->id, $ids, true))
        ->toBeLessThan(array_search($older->id, $ids, true));
});

it('orders the top sort by score', function (): void {
    $community = communityOwnedBy();
    Post::factory()->inCommunity($community)->create(['score' => 1]);
    $best = Post::factory()->inCommunity($community)->create(['score' => 50]);

    $response = $this->get(route('communities.show', [$community, 'sort' => 'top']));
    $ids = collect($response->viewData('page')['props']['posts'])->pluck('id');

    expect($ids->first())->toBe($best->id);
});

it('restricts the top sort to the selected range', function (): void {
    $community = communityOwnedBy();
    Post::factory()->inCommunity($community)->create([
        'score' => 100,
        'created_at' => now()->subYears(2),
    ]);
    $recent = Post::factory()->inCommunity($community)->create([
        'score' => 5,
        'created_at' => now(),
    ]);

    $response = $this->get(route('communities.show', [
        $community, 'sort' => 'top', 'range' => 'week',
    ]));

    $ids = collect($response->viewData('page')['props']['posts'])->pluck('id');

    expect($ids->all())->toBe([$recent->id]);
});

it('rejects an unknown sort', function (): void {
    $community = communityOwnedBy();

    $this->get(route('communities.show', [$community, 'sort' => 'bogus']))
        ->assertSessionHasErrors('sort');
});

it('renders a feed of many posts with a bounded query count', function (): void {
    $community = communityOwnedBy();
    $viewer = joinCommunity($community, User::factory()->create());

    Post::factory()->count(15)->inCommunity($community)->withImages(2)->create();

    DB::enableQueryLog();

    $this->actingAs($viewer)->get(route('communities.show', $community))->assertOk();

    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    // Constant number of queries regardless of how many posts are rendered.
    expect($queries)->toBeLessThan(25);
});

it('shows the aggregated home feed to guests', function (): void {
    $community = communityOwnedBy();
    Post::factory()->count(2)->inCommunity($community)->create();

    $this->get(route('home'))->assertOk();
});

it('limits the home feed to joined communities', function (): void {
    $joined = communityOwnedBy(null, ['name' => 'joinedone']);
    $other = communityOwnedBy(null, ['name' => 'otherone']);

    $viewer = joinCommunity($joined, User::factory()->create());

    $mine = Post::factory()->inCommunity($joined)->create();
    Post::factory()->inCommunity($other)->create();

    $response = $this->actingAs($viewer)->get(route('home'));
    $ids = collect($response->viewData('page')['props']['posts'])->pluck('id');

    expect($ids->all())->toBe([$mine->id]);
});
