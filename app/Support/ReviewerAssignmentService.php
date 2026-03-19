<?php

namespace App\Support;

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\ScreeningReview;
use App\Models\Submission;
use App\Models\TechnicalReview;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
use App\ReviewAssignmentType;
use App\ReviewerAssignmentStatus;
use App\SubmissionStatus;
use Illuminate\Validation\ValidationException;

class ReviewerAssignmentService
{
    public function assign(
        Submission $submission,
        Reviewer $reviewer,
        User $actor,
        ?string $dueAt = null,
        ReviewAssignmentType $assignmentType = ReviewAssignmentType::Screening,
    ): ReviewerAssignment {
        if (! in_array($submission->status, $this->allowedSubmissionStatuses($assignmentType), true)) {
            throw ValidationException::withMessages([
                'submission' => match ($assignmentType) {
                    ReviewAssignmentType::Screening => 'Only eligible submissions can be assigned to a screening reviewer.',
                    ReviewAssignmentType::Technical => 'Only shortlisted submissions can be assigned to a technical reviewer.',
                },
            ]);
        }

        $duplicateAssignmentExists = ReviewerAssignment::query()
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $reviewer->id)
            ->where('stage_id', $submission->current_stage_id)
            ->where('assignment_type', $assignmentType)
            ->whereIn('status', [
                ReviewerAssignmentStatus::Assigned,
                ReviewerAssignmentStatus::InProgress,
            ])
            ->exists();

        if ($duplicateAssignmentExists) {
            throw ValidationException::withMessages([
                'reviewer_id' => "This reviewer already has an active {$assignmentType->label()} assignment for the current submission stage.",
            ]);
        }

        $assignment = ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'stage_id' => $submission->current_stage_id,
            'assignment_type' => $assignmentType,
            'assigned_at' => now(),
            'due_at' => $dueAt,
            'status' => ReviewerAssignmentStatus::Assigned,
        ]);

        $reviewer->user?->notify(new SystemMessageNotification(
            title: "New {$assignmentType->label()} assignment",
            message: "You were assigned to review {$submission->title}.",
            actionUrl: $assignmentType === ReviewAssignmentType::Screening
                ? route('reviewer-queue.show', $assignment)
                : route('technical-reviewer-queue.show', $assignment),
            actionLabel: 'Open assignment',
        ));

        ActivityLogger::record(
            actor: $actor,
            event: 'negadras.reviewer-assignments.created',
            description: "Assigned reviewer {$reviewer->user?->name} to {$submission->title}.",
            subject: $assignment,
            properties: [
                'submission_id' => $submission->id,
                'reviewer_id' => $reviewer->id,
                'stage_id' => $submission->current_stage_id,
                'assignment_type' => $assignmentType->value,
            ],
        );

        return $assignment;
    }

    public function markInProgress(ReviewerAssignment $assignment): void
    {
        if ($assignment->status !== ReviewerAssignmentStatus::Assigned) {
            return;
        }

        $assignment->update([
            'status' => ReviewerAssignmentStatus::InProgress,
        ]);
    }

    public function complete(ReviewerAssignment $assignment): void
    {
        $assignment->update([
            'status' => ReviewerAssignmentStatus::Submitted,
        ]);
    }

    public function cancel(ReviewerAssignment $assignment, User $actor): void
    {
        $assignment->update([
            'status' => ReviewerAssignmentStatus::Cancelled,
        ]);

        ActivityLogger::record(
            actor: $actor,
            event: 'negadras.reviewer-assignments.cancelled',
            description: "Cancelled reviewer assignment for {$assignment->submission?->title}.",
            subject: $assignment,
            properties: [
                'submission_id' => $assignment->submission_id,
                'reviewer_id' => $assignment->reviewer_id,
            ],
        );
    }

    public function reassign(
        ReviewerAssignment $assignment,
        Reviewer $reviewer,
        User $actor,
        ?string $dueAt = null,
        ?string $reason = null,
    ): ReviewerAssignment {
        $submission = $assignment->submission()->firstOrFail();

        if ($assignment->status->isActive()) {
            $this->cancel($assignment, $actor);
        }

        $newAssignment = $this->assign(
            submission: $submission,
            reviewer: $reviewer,
            actor: $actor,
            dueAt: $dueAt,
            assignmentType: $assignment->assignment_type,
        );

        ActivityLogger::record(
            actor: $actor,
            event: 'negadras.reviewer-assignments.reassigned',
            description: "Reassigned reviewer for {$submission->title}.",
            subject: $newAssignment,
            properties: [
                'from_assignment_id' => $assignment->id,
                'from_reviewer_id' => $assignment->reviewer_id,
                'to_reviewer_id' => $reviewer->id,
                'reason' => $reason,
                'assignment_type' => $assignment->assignment_type->value,
            ],
        );

        return $newAssignment;
    }

    /**
     * @param  list<Reviewer>  $reviewers
     * @return list<ReviewerAssignment>
     */
    public function bulkAssign(
        Submission $submission,
        array $reviewers,
        User $actor,
        ?string $dueAt = null,
        ReviewAssignmentType $assignmentType = ReviewAssignmentType::Screening,
    ): array {
        return collect($reviewers)
            ->map(fn (Reviewer $reviewer): ReviewerAssignment => $this->assign(
                submission: $submission,
                reviewer: $reviewer,
                actor: $actor,
                dueAt: $dueAt,
                assignmentType: $assignmentType,
            ))
            ->all();
    }

    public function saveDraftReview(
        ReviewerAssignment $assignment,
        array $payload,
    ): ScreeningReview {
        $this->markInProgress($assignment);

        return ScreeningReview::query()->updateOrCreate(
            [
                'reviewer_assignment_id' => $assignment->id,
            ],
            [
                'submission_id' => $assignment->submission_id,
                'eligibility_status' => $payload['eligibility_status'],
                'eligibility_checklist' => $payload['eligibility_checklist'] ?? null,
                'recommendation' => $payload['recommendation'],
                'score_optional' => $payload['score_optional'],
                'notes' => $payload['notes'],
                'submitted_at' => null,
            ],
        );
    }

    public function submitReview(
        ReviewerAssignment $assignment,
        array $payload,
        User $actor,
    ): ScreeningReview {
        $review = $this->saveDraftReview($assignment, $payload);

        $review->update([
            'submitted_at' => now(),
        ]);

        $this->complete($assignment);

        ActivityLogger::record(
            actor: $actor,
            event: 'negadras.screening-reviews.submitted',
            description: "Submitted screening review for {$assignment->submission?->title}.",
            subject: $review,
            properties: [
                'submission_id' => $assignment->submission_id,
                'reviewer_assignment_id' => $assignment->id,
                'recommendation' => $payload['recommendation'],
            ],
        );

        User::role(['Admin', 'Manager'])
            ->get()
            ->each(function (User $user) use ($assignment): void {
                $user->notify(new SystemMessageNotification(
                    title: 'Screening review submitted',
                    message: "A screening review was submitted for {$assignment->submission?->title}.",
                    actionUrl: route('screening-queue.show', $assignment->submission_id),
                    actionLabel: 'Open screening queue',
                ));
            });

        return $review;
    }

    public function reopenReview(
        ReviewerAssignment $assignment,
        User $actor,
        string $reason,
    ): void {
        $assignment->update([
            'status' => ReviewerAssignmentStatus::InProgress,
        ]);

        if ($assignment->isScreening()) {
            $assignment->screeningReview?->update([
                'submitted_at' => null,
            ]);
        }

        if ($assignment->isTechnical()) {
            $assignment->technicalReview?->update([
                'submitted_at' => null,
            ]);
        }

        ActivityLogger::record(
            actor: $actor,
            event: 'negadras.reviewer-assignments.reopened',
            description: "Reopened {$assignment->assignment_type->label()} review for {$assignment->submission?->title}.",
            subject: $assignment,
            properties: [
                'assignment_type' => $assignment->assignment_type->value,
                'submission_id' => $assignment->submission_id,
                'reason' => $reason,
            ],
        );

        $assignment->reviewer?->user?->notify(new SystemMessageNotification(
            title: "{$assignment->assignment_type->label()} reopened",
            message: "Your {$assignment->assignment_type->label()} for {$assignment->submission?->title} was reopened for further work.",
            actionUrl: $assignment->isScreening()
                ? route('reviewer-queue.show', $assignment)
                : route('technical-reviewer-queue.show', $assignment),
            actionLabel: 'Open assignment',
            level: 'warning',
        ));
    }

    public function saveDraftTechnicalReview(
        ReviewerAssignment $assignment,
        array $payload,
    ): TechnicalReview {
        $this->markInProgress($assignment);

        return TechnicalReview::query()->updateOrCreate(
            [
                'reviewer_assignment_id' => $assignment->id,
            ],
            [
                'submission_id' => $assignment->submission_id,
                'reviewer_id' => $assignment->reviewer_id,
                'stage_id' => $assignment->stage_id,
                'innovation_score_optional' => $payload['innovation_score_optional'],
                'feasibility_score_optional' => $payload['feasibility_score_optional'],
                'execution_score_optional' => $payload['execution_score_optional'],
                'market_score_optional' => $payload['market_score_optional'],
                'strengths' => $payload['strengths'],
                'weaknesses' => $payload['weaknesses'],
                'risk_note' => $payload['risk_note'],
                'recommendation' => $payload['recommendation'],
                'submitted_at' => null,
            ],
        );
    }

    public function submitTechnicalReview(
        ReviewerAssignment $assignment,
        array $payload,
        User $actor,
    ): TechnicalReview {
        $review = $this->saveDraftTechnicalReview($assignment, $payload);

        $review->update([
            'submitted_at' => now(),
        ]);

        $this->complete($assignment);

        ActivityLogger::record(
            actor: $actor,
            event: 'negadras.technical-reviews.submitted',
            description: "Submitted technical review for {$assignment->submission?->title}.",
            subject: $review,
            properties: [
                'submission_id' => $assignment->submission_id,
                'reviewer_assignment_id' => $assignment->id,
                'recommendation' => $payload['recommendation'],
            ],
        );

        User::role(['Admin', 'Manager'])
            ->get()
            ->each(function (User $user) use ($assignment): void {
                $user->notify(new SystemMessageNotification(
                    title: 'Technical review submitted',
                    message: "A technical review was submitted for {$assignment->submission?->title}.",
                    actionUrl: route('technical-queue.show', $assignment->submission_id),
                    actionLabel: 'Open technical queue',
                ));
            });

        return $review;
    }

    /**
     * @return list<SubmissionStatus>
     */
    private function allowedSubmissionStatuses(ReviewAssignmentType $assignmentType): array
    {
        return match ($assignmentType) {
            ReviewAssignmentType::Screening => [SubmissionStatus::Eligible],
            ReviewAssignmentType::Technical => [SubmissionStatus::Shortlisted],
        };
    }
}
