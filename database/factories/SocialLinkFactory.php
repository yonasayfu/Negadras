<?php

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\SocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialLink>
 */
class SocialLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'applicant_id' => Applicant::factory(),
            'organization_id' => null,
            'platform' => fake()->randomElement(['LinkedIn', 'Telegram', 'Website', 'X']),
            'url' => fake()->url(),
            'is_verified' => fake()->boolean(20),
        ];
    }
}
