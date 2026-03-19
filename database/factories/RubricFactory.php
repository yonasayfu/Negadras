<?php

namespace Database\Factories;

use App\Models\Rubric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rubric>
 */
class RubricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'total_weight' => 100,
            'is_active' => true,
        ];
    }
}
