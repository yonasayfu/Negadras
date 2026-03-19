<?php

namespace App\Support;

use App\Models\ReviewerAssignment;
use App\Models\User;
use App\ReviewerAssignmentStatus;

class ReviewerAssignmentOverdueService
{
    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function expireOverdueAssignments(): int
    {
        $expired = 0;

        ReviewerAssignment::query()
            ->with(['reviewer.user', 'submission'])
            ->whereIn('status', [
                ReviewerAssignmentStatus::Assigned,
                ReviewerAssignmentStatus::InProgress,
            ])
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->get()
            ->each(function (ReviewerAssignment $assignment) use (&$expired): void {
                $assignment->update([
                    'status' => ReviewerAssignmentStatus::Expired,
                ]);

                $expired++;

                if ($assignment->reviewer?->user !== null) {
                    $this->notifications->sendToUser(
                        recipient: $assignment->reviewer->user,
                        category: 'reviewer-assignment-overdue',
                        title: 'Review assignment overdue',
                        message: "Your {$assignment->assignment_type->label()} assignment for {$assignment->submission?->title} is overdue.",
                        actionUrl: $assignment->isTechnical()
                            ? route('technical-reviewer-queue.show', $assignment)
                            : route('reviewer-queue.show', $assignment),
                        actionLabel: 'Open assignment',
                        level: 'warning',
                        context: $assignment,
                    );
                }

                $this->notifications->send(
                    recipients: User::role(['Admin', 'Manager'])->get(),
                    category: 'reviewer-assignment-overdue',
                    title: 'Reviewer assignment overdue',
                    message: "An overdue {$assignment->assignment_type->label()} assignment exists for {$assignment->submission?->title}.",
                    actionUrl: $assignment->isTechnical()
                        ? route('technical-queue.show', $assignment->submission_id)
                        : route('screening-queue.show', $assignment->submission_id),
                    actionLabel: 'Open workflow',
                    level: 'warning',
                    context: $assignment,
                );

                ActivityLogger::record(
                    actor: $assignment->reviewer?->user,
                    event: 'negadras.reviewer-assignments.expired',
                    description: "Reviewer assignment expired for {$assignment->submission?->title}.",
                    subject: $assignment,
                    properties: [
                        'assignment_type' => $assignment->assignment_type->value,
                        'submission_id' => $assignment->submission_id,
                    ],
                );
            });

        return $expired;
    }
}
