<?php

namespace Database\Factories;

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\ReviewerAssignmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewerAssignment>
 */
class ReviewerAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submission = Submission::factory()->create();

        return [
            'submission_id' => $submission->id,
            'reviewer_id' => Reviewer::factory(),
            'stage_id' => $submission->current_stage_id,
            'assigned_at' => now()->subDay(),
            'due_at' => now()->addDays(3),
            'status' => ReviewerAssignmentStatus::Assigned,
        ];
    }
}
