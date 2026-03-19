<?php

namespace Database\Factories;

use App\ConflictOfInterestStatus;
use App\ConflictOfInterestType;
use App\Models\ConflictOfInterestDeclaration;
use App\Models\Judge;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConflictOfInterestDeclaration>
 */
class ConflictOfInterestDeclarationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'judge_id' => Judge::factory(),
            'submission_id' => Submission::factory(),
            'session_id_optional' => null,
            'conflict_type' => ConflictOfInterestType::SelfDeclared,
            'description' => fake()->sentence(),
            'declared_at' => now(),
            'status' => ConflictOfInterestStatus::Active,
        ];
    }
}
