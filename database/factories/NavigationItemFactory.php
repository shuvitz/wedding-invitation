<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->words(2, true),
            'url' => '/'.fake()->slug(2),
            'order' => fake()->numberBetween(0, 10),
            'visible' => true,
        ];
    }

    /**
     * Indicate that the item is hidden.
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'visible' => false,
        ]);
    }
}
