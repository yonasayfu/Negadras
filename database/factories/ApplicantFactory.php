<?php

namespace Database\Factories;

use App\ApplicantType;
use App\Models\Applicant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Applicant>
 */
class ApplicantFactory extends Factory
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
            'applicant_type' => fake()->randomElement(ApplicantType::cases()),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->numerify('09########'),
            'bio' => fake()->optional()->paragraph(),
            'national_id_or_registration_ref' => fake()->optional()->bothify('NEG-####-??'),
        ];
    }
}
