<?php

namespace App\Support;

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\ScreeningReview;
use App\Models\Submission;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
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
    ): ReviewerAssignment {
        if ($submission->status !== SubmissionStatus::Eligible) {
            throw ValidationException::withMessages([
                'submission' => 'Only eligible submissions can be assigned to a reviewer.',
            ]);
        }

        $duplicateAssignmentExists = ReviewerAssignment::query()
            ->where('submission_id', $submission->id)
            ->where('reviewer_id', $reviewer->id)
            ->where('stage_id', $submission->current_stage_id)
            ->whereIn('status', [
                ReviewerAssignmentStatus::Assigned,
                ReviewerAssignmentStatus::InProgress,
            ])
            ->exists();

        if ($duplicateAssignmentExists) {
            throw ValidationException::withMessages([
                'reviewer_id' => 'This reviewer already has an active assignment for the current submission stage.',
            ]);
        }

        $assignment = ReviewerAssignment::query()->create([
            'submission_id' => $submission->id,
            'reviewer_id' => $reviewer->id,
            'stage_id' => $submission->current_stage_id,
            'assigned_at' => now(),
            'due_at' => $dueAt,
            'status' => ReviewerAssignmentStatus::Assigned,
        ]);

        $reviewer->user?->notify(new SystemMessageNotification(
            title: 'New screening assignment',
            message: "You were assigned to review {$submission->title}.",
            actionUrl: route('reviewer-queue.show', $assignment),
            actionLabel: 'Open review',
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
            ],
        );

        return $newAssignment;
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
}
