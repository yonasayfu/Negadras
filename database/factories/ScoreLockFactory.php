<?php

namespace Database\Factories;

use App\Models\PanelSubmissionAssignment;
use App\Models\ScoreLock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreLock>
 */
class ScoreLockFactory extends Factory
{
    public function definition(): array
    {
        return [
            'panel_submission_assignment_id' => PanelSubmissionAssignment::factory(),
            'locked_by' => User::factory(),
            'locked_at' => now(),
            'reason' => fake()->sentence(),
            'reopened_by' => null,
            'reopened_at' => null,
            'reopen_reason' => null,
        ];
    }
}
