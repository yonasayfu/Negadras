<?php

namespace Database\Factories;

use App\Models\Panel;
use App\Models\PanelSubmissionAssignment;
use App\Models\Submission;
use App\PanelSubmissionAssignmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanelSubmissionAssignment>
 */
class PanelSubmissionAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'panel_id' => Panel::factory(),
            'submission_id' => Submission::factory(),
            'session_id_optional' => null,
            'assigned_at' => now(),
            'status' => PanelSubmissionAssignmentStatus::Assigned,
        ];
    }
}
