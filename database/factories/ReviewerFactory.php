<?php

namespace Database\Factories;

use App\Models\Reviewer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reviewer>
 */
class ReviewerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'professional_title' => fake()->jobTitle(),
            'organization' => fake()->company(),
            'specialization' => fake()->randomElement(['Innovation', 'Climate', 'Agriculture', 'Fintech', 'Health']),
            'bio' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
