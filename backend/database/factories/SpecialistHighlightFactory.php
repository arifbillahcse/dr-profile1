<?php

namespace Database\Factories;

use App\Models\SpecialistHighlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SpecialistHighlight> */
class SpecialistHighlightFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'sort_order' => 0,
            'is_active' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
