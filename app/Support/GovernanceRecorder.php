<?php

namespace App\Support;

use App\Models\CompetitionSession;
use App\Models\OverrideEvent;
use App\Models\PanelSubmissionAssignment;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\OverrideEventType;

class GovernanceRecorder
{
    /**
     * @param  array<string, mixed>|null  $beforeState
     * @param  array<string, mixed>|null  $afterState
     */
    public function record(
        OverrideEventType $eventType,
        ?User $actor = null,
        ?string $reason = null,
        ?Submission $submission = null,
        ?CompetitionSession $competitionSession = null,
        ?PanelSubmissionAssignment $panelSubmissionAssignment = null,
        ?ReviewerAssignment $reviewerAssignment = null,
        ?array $beforeState = null,
        ?array $afterState = null,
    ): OverrideEvent {
        return OverrideEvent::query()->create([
            'actor_id' => $actor?->id,
            'submission_id' => $submission?->id,
            'competition_session_id' => $competitionSession?->id,
            'panel_submission_assignment_id' => $panelSubmissionAssignment?->id,
            'reviewer_assignment_id' => $reviewerAssignment?->id,
            'event_type' => $eventType,
            'reason' => $reason,
            'before_state' => $beforeState,
            'after_state' => $afterState,
        ]);
    }
}
