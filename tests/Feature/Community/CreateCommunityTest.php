<?php

use App\Models\Community;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('redirects guests away from the create form', function (): void {
    $this->get(route('communities.create'))->assertRedirect(route('login'));
});

it('creates a community with the creator as admin and creator', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('communities.store'), [
            'name' => 'webdev',
            'title' => 'Web Development',
            'description' => 'All things web.',
        ])
        ->assertRedirect(route('communities.show', 'webdev'));

    $community = Community::query()->where('name', 'webdev')->firstOrFail();

    expect($community->members_count)->toBe(1)
        ->and($community->created_by)->toBe($user->id);

    $pivot = $community->members()->whereKey($user->id)->firstOrFail()->pivot;

    expect($pivot->is_creator)->toBeTrue()
        ->and($pivot->role->value)->toBe('admin');
});

it('rejects a duplicate name regardless of casing', function (): void {
    communityOwnedBy(null, ['name' => 'webdev']);

    $this->actingAs(User::factory()->create())
        ->post(route('communities.store'), [
            'name' => 'WebDev',
            'title' => 'Duplicate',
        ])
        ->assertSessionHasErrors('name');
});

it('rejects a reserved name', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('communities.store'), ['name' => 'admin', 'title' => 'Nope'])
        ->assertSessionHasErrors('name');
});

it('rejects an invalid name', function (): void {
    $this->actingAs(User::factory()->create())
        ->post(route('communities.store'), ['name' => 'no spaces!', 'title' => 'Nope'])
        ->assertSessionHasErrors('name');
});

it('stores the avatar and banner on the s3 disk', function (): void {
    Storage::fake('s3');

    $this->actingAs(User::factory()->create())
        ->post(route('communities.store'), [
            'name' => 'photos',
            'title' => 'Photos',
            'avatar' => UploadedFile::fake()->image('a.png', 200, 200),
            'banner' => UploadedFile::fake()->image('b.png', 1200, 300),
        ])
        ->assertSessionHasNoErrors();

    $community = Community::query()->where('name', 'photos')->firstOrFail();

    Storage::disk('s3')->assertExists($community->avatar_path);
    Storage::disk('s3')->assertExists($community->banner_path);
});
