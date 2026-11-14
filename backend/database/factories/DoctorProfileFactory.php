<?php

namespace Database\Factories;

use App\Models\DoctorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DoctorProfile> */
class DoctorProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->uuid(),
            'name' => fake()->name(),
            'credentials' => [],
        ];
    }
}
