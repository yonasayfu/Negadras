<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionStatusHistory>
 */
class SubmissionStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'from_status' => SubmissionStatus::Draft,
            'to_status' => SubmissionStatus::Submitted,
            'changed_by' => User::factory(),
            'reason' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
