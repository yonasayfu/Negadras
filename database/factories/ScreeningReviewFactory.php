<?php

namespace Database\Factories;

use App\Models\ReviewerAssignment;
use App\Models\ScreeningReview;
use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScreeningReview>
 */
class ScreeningReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $assignment = ReviewerAssignment::factory()->create();

        return [
            'submission_id' => $assignment->submission_id,
            'reviewer_assignment_id' => $assignment->id,
            'eligibility_status' => fake()->randomElement(ScreeningEligibilityStatus::cases()),
            'recommendation' => fake()->randomElement(ScreeningRecommendation::cases()),
            'score_optional' => fake()->numberBetween(50, 95),
            'notes' => fake()->paragraph(),
            'submitted_at' => null,
        ];
    }
}
