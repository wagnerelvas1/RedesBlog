<?php

namespace Database\Factories;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => config('filesystems.default'),
            'path' => 'attachments/'.Str::ulid().'.png',
            'original_name' => fake()->word().'.png',
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(2_000, 500_000),
            'width' => 800,
            'height' => 600,
            'position' => 0,
        ];
    }

    /**
     * Marks the attachment as a JPEG with realistic photo dimensions.
     */
    public function forImage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'mime_type' => 'image/jpeg',
            'original_name' => fake()->word().'.jpg',
            'path' => 'attachments/'.Str::ulid().'.jpg',
            'width' => 1600,
            'height' => 900,
        ]);
    }

    public function atPosition(int $position): static
    {
        return $this->state(fn (array $attributes): array => [
            'position' => $position,
        ]);
    }
}
