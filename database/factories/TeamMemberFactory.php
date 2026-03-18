<?php

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\Organization;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'applicant_id' => null,
            'full_name' => fake()->name(),
            'role_title' => fake()->jobTitle(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->optional()->numerify('09########'),
            'bio' => fake()->optional()->sentence(),
            'is_primary_contact' => false,
        ];
    }

    public function linkedApplicant(): static
    {
        return $this->state(fn (): array => [
            'applicant_id' => Applicant::factory(),
        ]);
    }
}
