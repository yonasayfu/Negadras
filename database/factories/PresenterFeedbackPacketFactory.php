<?php

namespace Database\Factories;

use App\FeedbackVisibilityStatus;
use App\Models\PresenterFeedbackPacket;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PresenterFeedbackPacket>
 */
class PresenterFeedbackPacketFactory extends Factory
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
            'summary' => fake()->paragraph(),
            'strengths' => fake()->paragraph(),
            'improvement_areas' => fake()->paragraph(),
            'next_step_guidance' => fake()->sentence(),
            'generated_by' => null,
            'visibility_status' => FeedbackVisibilityStatus::InternalReview,
            'score_summary_optional' => fake()->randomFloat(2, 60, 100),
            'sent_at_optional' => null,
        ];
    }
}
