<?php

namespace Database\Factories;

use App\Models\Community;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Community>
 */
class CommunityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => Str::lower(fake()->unique()->regexify('[A-Za-z]{5,15}')),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(12),
            'rules' => '1. Be civil.'.PHP_EOL.'2. Stay on topic.',
            'is_private' => false,
            'created_by' => User::factory(),
            'members_count' => 0,
            'posts_count' => 0,
        ];
    }

    public function withImages(): static
    {
        return $this->state(fn (array $attributes): array => [
            'avatar_path' => 'communities/'.Str::ulid().'.png',
            'banner_path' => 'communities/'.Str::ulid().'.png',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_private' => true,
        ]);
    }

    /**
     * Creates the community already owned by the given user, with the pivot
     * row that marks them as the immovable creator.
     */
    public function createdBy(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'created_by' => $user->id,
        ])->afterCreating(function (Community $community) use ($user): void {
            $community->members()->attach($user->id, [
                'role' => 'admin',
                'is_creator' => true,
            ]);
            $community->members_count = 1;
            $community->save();
        });
    }
}
