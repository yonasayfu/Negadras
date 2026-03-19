<?php

namespace Database\Factories;

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\TechnicalReview;
use App\ReviewAssignmentType;
use App\ReviewerAssignmentStatus;
use App\TechnicalReviewRecommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TechnicalReview>
 */
class TechnicalReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submission = Submission::factory()->create();
        $reviewer = Reviewer::factory()->create();
        $assignment = ReviewerAssignment::factory()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'stage_id' => $submission->current_stage_id,
            'status' => ReviewerAssignmentStatus::Assigned,
            'assignment_type' => ReviewAssignmentType::Technical,
        ]);

        return [
            'submission_id' => $submission->id,
            'reviewer_assignment_id' => $assignment->id,
            'reviewer_id' => $reviewer->id,
            'stage_id' => $submission->current_stage_id,
            'innovation_score_optional' => fake()->numberBetween(50, 95),
            'feasibility_score_optional' => fake()->numberBetween(50, 95),
            'execution_score_optional' => fake()->numberBetween(50, 95),
            'market_score_optional' => fake()->numberBetween(50, 95),
            'strengths' => fake()->paragraph(),
            'weaknesses' => fake()->paragraph(),
            'risk_note' => fake()->sentence(),
            'recommendation' => TechnicalReviewRecommendation::Advance,
            'submitted_at' => null,
        ];
    }
}
