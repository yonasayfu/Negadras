<?php

namespace App\Support;

use App\Models\ReviewDecision;
use App\Models\ShortlistRecord;
use App\Models\Submission;
use App\Models\User;
use App\ReviewDecisionType;
use App\ShortlistApprovalStatus;
use App\SubmissionStatus;
use Illuminate\Validation\ValidationException;

class ReviewDecisionService
{
    public function record(
        Submission $submission,
        ReviewDecisionType $decisionType,
        User $actor,
        ?string $reason = null,
    ): ReviewDecision {
        if ($decisionType !== ReviewDecisionType::NeedsMoreReview && blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for this decision.',
            ]);
        }

        if ($decisionType === ReviewDecisionType::Shortlisted) {
            ShortlistRecord::query()->firstOrCreate(
                [
                    'submission_id' => $submission->id,
                    'stage_id' => $submission->current_stage_id,
                ],
                [
                    'notes' => $reason,
                    'created_by' => $actor->id,
                    'approval_status' => ShortlistApprovalStatus::Pending,
                ],
            );
        }

        return ReviewDecision::query()->create([
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'decision_type' => $decisionType,
            'decision_reason' => $reason,
            'decided_by' => $actor->id,
            'decided_at' => now(),
        ]);
    }

    public function fromSubmissionStatus(SubmissionStatus $status): ReviewDecisionType
    {
        return match ($status) {
            SubmissionStatus::Shortlisted => ReviewDecisionType::Shortlisted,
            SubmissionStatus::Rejected => ReviewDecisionType::Rejected,
            SubmissionStatus::IncompleteReturned => ReviewDecisionType::ReturnedForRevision,
            default => ReviewDecisionType::NeedsMoreReview,
        };
    }

    public function allowsStatusTransition(ReviewDecisionType $decisionType): bool
    {
        return in_array($decisionType, [
            ReviewDecisionType::Shortlisted,
            ReviewDecisionType::Rejected,
            ReviewDecisionType::ReturnedForRevision,
        ], true);
    }

    public function toSubmissionStatus(ReviewDecisionType $decisionType): ?SubmissionStatus
    {
        return match ($decisionType) {
            ReviewDecisionType::Shortlisted => SubmissionStatus::Shortlisted,
            ReviewDecisionType::Rejected => SubmissionStatus::Rejected,
            ReviewDecisionType::ReturnedForRevision => SubmissionStatus::IncompleteReturned,
            ReviewDecisionType::NeedsMoreReview => null,
        };
    }
}
