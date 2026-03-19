<?php

namespace Database\Factories;

use App\JudgeCommentType;
use App\Models\Judge;
use App\Models\JudgeComment;
use App\Models\PanelSubmissionAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JudgeComment>
 */
class JudgeCommentFactory extends Factory
{
    public function definition(): array
    {
        $assignment = PanelSubmissionAssignment::factory()->create();

        return [
            'submission_id' => $assignment->submission_id,
            'judge_id' => Judge::factory(),
            'panel_submission_assignment_id' => $assignment->id,
            'session_id_optional' => null,
            'comment_type' => JudgeCommentType::Private,
            'content' => fake()->sentence(),
            'is_archived' => false,
            'created_at' => now(),
        ];
    }
}
