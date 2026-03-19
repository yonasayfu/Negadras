<?php

namespace Database\Factories;

use App\Models\PanelSubmissionAssignment;
use App\Models\ScoreVisibilityEvent;
use App\Models\User;
use App\ScoreVisibilityAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreVisibilityEvent>
 */
class ScoreVisibilityEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'panel_submission_assignment_id' => PanelSubmissionAssignment::factory(),
            'action' => ScoreVisibilityAction::Hide,
            'note' => fake()->sentence(),
            'changed_by' => User::factory(),
            'changed_at' => now(),
        ];
    }
}
