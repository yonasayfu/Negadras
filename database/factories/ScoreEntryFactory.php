<?php

namespace Database\Factories;

use App\Models\Judge;
use App\Models\PanelSubmissionAssignment;
use App\Models\RubricCriterion;
use App\Models\ScoreEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScoreEntry>
 */
class ScoreEntryFactory extends Factory
{
    public function definition(): array
    {
        $assignment = PanelSubmissionAssignment::factory()->create();
        $criterion = RubricCriterion::factory()->create([
            'rubric_id' => $assignment->panel->rubric_id,
        ]);

        return [
            'submission_id' => $assignment->submission_id,
            'panel_submission_assignment_id' => $assignment->id,
            'session_id_optional' => null,
            'panel_id_optional' => $assignment->panel_id,
            'judge_id' => Judge::factory(),
            'rubric_criterion_id' => $criterion->id,
            'score_value' => fake()->numberBetween(1, 10),
            'comment' => fake()->sentence(),
            'is_secret' => true,
            'is_locked' => false,
            'submitted_at' => now(),
        ];
    }
}
