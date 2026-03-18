<?php

namespace Database\Factories;

use App\Models\Industry;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'legal_name' => fake()->company().' PLC',
            'display_name' => fake()->company(),
            'registration_number' => strtoupper(fake()->bothify('NEG-#####')),
            'industry_id' => Industry::factory(),
            'website' => fake()->optional()->url(),
            'description' => fake()->optional()->paragraph(),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->optional()->numerify('09########'),
            'logo_path' => null,
            'address' => fake()->optional()->address(),
        ];
    }
}
