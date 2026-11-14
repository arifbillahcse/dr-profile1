<?php

namespace Database\Factories;

use App\Models\FeaturedStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeaturedStatistic> */
class FeaturedStatisticFactory extends Factory
{
    public function definition(): array
    {
        return [
            'label' => fake()->words(2, true),
            'value' => '2000',
            'suffix' => '+',
            'sort_order' => 0,
            'is_active' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }
}
