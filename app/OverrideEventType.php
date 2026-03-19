<?php

namespace App;

enum OverrideEventType: string
{
    case SubmissionStatusTransition = 'submission_status_transition';
    case ReviewerReassigned = 'reviewer_reassigned';
    case ReviewReopened = 'review_reopened';
    case RankingOverride = 'ranking_override';
    case ScoreLockUpdated = 'score_lock_updated';
    case ScoreVisibilityUpdated = 'score_visibility_updated';
    case ConflictDecision = 'conflict_decision';

    public function label(): string
    {
        return match ($this) {
            self::SubmissionStatusTransition => 'Submission status transition',
            self::ReviewerReassigned => 'Reviewer reassigned',
            self::ReviewReopened => 'Review reopened',
            self::RankingOverride => 'Ranking override',
            self::ScoreLockUpdated => 'Score lock updated',
            self::ScoreVisibilityUpdated => 'Score visibility updated',
            self::ConflictDecision => 'Conflict decision',
        };
    }
}
