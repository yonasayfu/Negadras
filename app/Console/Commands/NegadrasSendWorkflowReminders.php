<?php

namespace App\Console\Commands;

use App\Models\PresenterFeedbackPacket;
use App\Models\User;
use App\Support\NotificationDispatcher;
use App\Support\ReviewerAssignmentOverdueService;
use Illuminate\Console\Command;

class NegadrasSendWorkflowReminders extends Command
{
    protected $signature = 'negadras:send-workflow-reminders';

    protected $description = 'Dispatch Negadras workflow reminder notifications.';

    public function handle(
        ReviewerAssignmentOverdueService $overdueService,
        NotificationDispatcher $notifications,
    ): int {
        $expiredCount = $overdueService->expireOverdueAssignments();
        $unreleasedFeedbackCount = PresenterFeedbackPacket::query()
            ->whereNull('sent_at_optional')
            ->count();

        if ($unreleasedFeedbackCount > 0) {
            $notifications->send(
                recipients: User::role(['Admin', 'Manager'])->get(),
                category: 'feedback-packet-reminder',
                title: 'Feedback packets pending release',
                message: "{$unreleasedFeedbackCount} feedback packets are still waiting to be released to presenters.",
                actionUrl: route('feedback-packets.index'),
                actionLabel: 'Open feedback packets',
                level: 'warning',
            );
        }

        $this->info("Expired {$expiredCount} assignments and checked {$unreleasedFeedbackCount} feedback packets.");

        return self::SUCCESS;
    }
}
