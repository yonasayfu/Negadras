<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionVersion>
 */
class SubmissionVersionFactory extends Factory
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
            'version_no' => 1,
            'snapshot_json' => [
                'title' => fake()->sentence(3),
                'status' => 'submitted',
            ],
            'change_note' => fake()->sentence(),
            'created_by' => User::factory(),
            'is_locked' => true,
        ];
    }
}
