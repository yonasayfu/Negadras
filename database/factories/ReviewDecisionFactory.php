<?php

namespace Database\Factories;

use App\Models\ReviewDecision;
use App\Models\Submission;
use App\Models\User;
use App\ReviewDecisionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewDecision>
 */
class ReviewDecisionFactory extends Factory
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
            'stage_id' => $submission->current_stage_id,
            'decision_type' => fake()->randomElement(ReviewDecisionType::cases()),
            'decision_reason' => fake()->sentence(),
            'decided_by' => User::factory(),
            'decided_at' => now(),
        ];
    }
}
