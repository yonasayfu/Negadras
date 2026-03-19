<?php

namespace Database\Factories;

use App\Models\OverrideEvent;
use App\Models\Submission;
use App\Models\User;
use App\OverrideEventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OverrideEvent>
 */
class OverrideEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'actor_id' => User::factory(),
            'submission_id' => Submission::factory(),
            'competition_session_id' => null,
            'panel_submission_assignment_id' => null,
            'reviewer_assignment_id' => null,
            'event_type' => OverrideEventType::SubmissionStatusTransition,
            'reason' => fake()->sentence(),
            'before_state' => ['status' => 'eligible'],
            'after_state' => ['status' => 'shortlisted'],
        ];
    }
}
