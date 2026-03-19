<?php

namespace App\Support;

use App\Models\Submission;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use App\SubmissionStatus;
use Illuminate\Validation\ValidationException;

class SubmissionStatusTransitionService
{
    /**
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        'draft' => ['submitted'],
        'submitted' => ['under_intake_check', 'incomplete_returned', 'eligible', 'rejected'],
        'under_intake_check' => ['incomplete_returned', 'eligible', 'rejected'],
        'incomplete_returned' => ['submitted'],
        'eligible' => [],
        'rejected' => [],
    ];

    /**
     * @return array<int, array{value: string, label: string, requiresReason: bool}>
     */
    public function availableStaffTransitions(Submission $submission): array
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

        $submission->forceFill([
            'status' => $toStatus,
            'submitted_at' => $toStatus === SubmissionStatus::Submitted
                ? ($submission->submitted_at ?? now())
                : $submission->submitted_at,
        ])->save();

        return $submission->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $actor?->id,
            'reason' => filled($reason) ? $reason : null,
            'created_at' => now(),
        ]);
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
}
