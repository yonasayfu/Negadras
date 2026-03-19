<?php

namespace Database\Factories;

use App\Models\Judge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Judge>
 */
class JudgeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'professional_title' => fake()->jobTitle(),
            'organization' => fake()->company(),
            'specialization' => fake()->words(3, true),
            'bio' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
