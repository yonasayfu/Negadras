<?php

namespace App\Support;

use App\Models\ReviewerAssignment;
use App\Models\User;
use App\Notifications\SystemMessageNotification;
use App\ReviewerAssignmentStatus;

class ReviewerAssignmentOverdueService
{
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

                $assignment->reviewer?->user?->notify(new SystemMessageNotification(
                    title: 'Review assignment overdue',
                    message: "Your {$assignment->assignment_type->label()} assignment for {$assignment->submission?->title} is overdue.",
                    actionUrl: $assignment->isTechnical()
                        ? route('technical-reviewer-queue.show', $assignment)
                        : route('reviewer-queue.show', $assignment),
                    actionLabel: 'Open assignment',
                    level: 'warning',
                ));

                User::role(['Admin', 'Manager'])
                    ->get()
                    ->each(function (User $user) use ($assignment): void {
                        $user->notify(new SystemMessageNotification(
                            title: 'Reviewer assignment overdue',
                            message: "An overdue {$assignment->assignment_type->label()} assignment exists for {$assignment->submission?->title}.",
                            actionUrl: $assignment->isTechnical()
                                ? route('technical-queue.show', $assignment->submission_id)
                                : route('screening-queue.show', $assignment->submission_id),
                            actionLabel: 'Open workflow',
                            level: 'warning',
                        ));
                    });

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
