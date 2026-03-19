<?php

namespace App\Support;

use App\Models\ReviewerAssignment;
use App\Models\ScreeningReview;
use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\OverrideEventType;
use App\ReviewerAssignmentStatus;
use App\SubmissionStatus;
use Illuminate\Validation\ValidationException;

class SubmissionStatusTransitionService
{
    public function __construct(
        private readonly GovernanceRecorder $governance,
    ) {}

    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['under_intake_check', 'incomplete_returned', 'eligible', 'rejected'],
        'under_intake_check' => ['incomplete_returned', 'eligible', 'rejected'],
        'incomplete_returned' => ['submitted'],
        'eligible' => ['incomplete_returned', 'shortlisted', 'rejected'],
        'shortlisted' => ['incomplete_returned', 'rejected'],
        'rejected' => [],
    ];

    /**
     * @return array<int, array{value: string, label: string, requiresReason: bool}>
     */
    public function availableIntakeTransitions(Submission $submission): array
    {
        return collect(self::TRANSITIONS[$submission->status->value] ?? [])
            ->filter(fn (string $status): bool => in_array($status, [
                SubmissionStatus::UnderIntakeCheck->value,
                SubmissionStatus::IncompleteReturned->value,
                SubmissionStatus::Eligible->value,
                SubmissionStatus::Rejected->value,
            ], true))
            ->map(fn (string $status): array => [
                'value' => $status,
                'label' => SubmissionStatus::from($status)->label(),
                'requiresReason' => in_array($status, [
                    SubmissionStatus::IncompleteReturned->value,
                    SubmissionStatus::Rejected->value,
                ], true),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, requiresReason: bool}>
     */
    public function availableScreeningDecisionTransitions(Submission $submission): array
    {
        if ($submission->status !== SubmissionStatus::Eligible) {
            return [];
        }

        if (! $this->hasSubmittedScreeningReview($submission)) {
            return [];
        }

        return collect(self::TRANSITIONS[$submission->status->value] ?? [])
            ->filter(fn (string $status): bool => in_array($status, [
                SubmissionStatus::IncompleteReturned->value,
                SubmissionStatus::Shortlisted->value,
                SubmissionStatus::Rejected->value,
            ], true))
            ->map(fn (string $status): array => [
                'value' => $status,
                'label' => SubmissionStatus::from($status)->label(),
                'requiresReason' => in_array($status, [
                    SubmissionStatus::IncompleteReturned->value,
                    SubmissionStatus::Rejected->value,
                ], true),
            ])
            ->values()
            ->all();
    }

    public function transition(
        Submission $submission,
        SubmissionStatus $toStatus,
        ?User $actor = null,
        ?string $reason = null,
    ): SubmissionStatusHistory {
        $fromStatus = $submission->status;

        if (! $this->canTransition($fromStatus, $toStatus)) {
            throw ValidationException::withMessages([
                'status' => "Submission cannot move from {$fromStatus->label()} to {$toStatus->label()}.",
            ]);
        }

        if ($this->requiresReason($toStatus) && blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for this status change.',
            ]);
        }

        if ($fromStatus === SubmissionStatus::Eligible
            && in_array($toStatus, [
                SubmissionStatus::IncompleteReturned,
                SubmissionStatus::Shortlisted,
                SubmissionStatus::Rejected,
            ], true)
            && ! $this->hasSubmittedScreeningReview($submission)) {
            throw ValidationException::withMessages([
                'status' => 'A submitted screening review is required before a manager can make a screening decision.',
            ]);
        }

        $submission->forceFill([
            'status' => $toStatus,
            'submitted_at' => $toStatus === SubmissionStatus::Submitted
                ? ($submission->submitted_at ?? now())
                : $submission->submitted_at,
        ])->save();

        if (in_array($fromStatus, [SubmissionStatus::Eligible, SubmissionStatus::Shortlisted], true)
            && in_array($toStatus, [
                SubmissionStatus::IncompleteReturned,
                SubmissionStatus::Shortlisted,
                SubmissionStatus::Rejected,
            ], true)) {
            ReviewerAssignment::query()
                ->where('submission_id', $submission->id)
                ->whereIn('status', [
                    ReviewerAssignmentStatus::Assigned,
                    ReviewerAssignmentStatus::InProgress,
                ])
                ->update([
                    'status' => ReviewerAssignmentStatus::Cancelled,
                ]);
        }

        $history = $submission->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $actor?->id,
            'reason' => filled($reason) ? $reason : null,
            'created_at' => now(),
        ]);

        if ($fromStatus !== $toStatus) {
            $this->governance->record(
                eventType: OverrideEventType::SubmissionStatusTransition,
                actor: $actor,
                reason: $reason,
                submission: $submission,
                beforeState: ['status' => $fromStatus->value],
                afterState: ['status' => $toStatus->value],
            );
        }

        return $history;
    }

    public function recordInitialStatus(
        Submission $submission,
        SubmissionStatus $toStatus,
        ?User $actor = null,
        ?string $reason = null,
    ): SubmissionStatusHistory {
        return $submission->statusHistory()->create([
            'from_status' => null,
            'to_status' => $toStatus,
            'changed_by' => $actor?->id,
            'reason' => filled($reason) ? $reason : null,
            'created_at' => now(),
        ]);
    }

    public function requiresReason(SubmissionStatus $toStatus): bool
    {
        return in_array($toStatus, [
            SubmissionStatus::IncompleteReturned,
            SubmissionStatus::Rejected,
        ], true);
    }

    private function canTransition(SubmissionStatus $fromStatus, SubmissionStatus $toStatus): bool
    {
        return in_array($toStatus->value, self::TRANSITIONS[$fromStatus->value] ?? [], true);
    }

    private function hasSubmittedScreeningReview(Submission $submission): bool
    {
        return ScreeningReview::query()
            ->where('submission_id', $submission->id)
            ->whereNotNull('submitted_at')
            ->exists();
    }
}
