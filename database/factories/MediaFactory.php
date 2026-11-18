<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = Str::uuid()->toString().'.jpg';

        return [
            'disk' => 'public',
            'path' => 'media/'.now()->format('Y/m')."/{$filename}",
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(1024, 500_000),
            'width' => fake()->numberBetween(200, 2000),
            'height' => fake()->numberBetween(200, 2000),
            'alt_text' => null,
            'uploaded_by' => null,
        ];
    }
}
