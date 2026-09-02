<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'value' => 1,
        ];
    }

    public function up(): static
    {
        return $this->state(fn (array $attributes): array => ['value' => 1]);
    }

    public function down(): static
    {
        return $this->state(fn (array $attributes): array => ['value' => -1]);
    }

    public function forVotable(Model $votable): static
    {
        return $this->state(fn (array $attributes): array => [
            'votable_type' => $votable->getMorphClass(),
            'votable_id' => $votable->getKey(),
        ]);
    }

    public function by(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }
}
