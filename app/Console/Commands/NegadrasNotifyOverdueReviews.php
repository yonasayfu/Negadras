<?php

namespace App\Console\Commands;

use App\Support\ReviewerAssignmentOverdueService;
use Illuminate\Console\Command;

class NegadrasNotifyOverdueReviews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'negadras:notify-overdue-reviews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire overdue reviewer assignments and send in-app notifications.';

    /**
     * Execute the console command.
     */
    public function handle(ReviewerAssignmentOverdueService $overdueService): int
    {
        $expiredCount = $overdueService->expireOverdueAssignments();

        $this->info("Expired {$expiredCount} overdue assignments.");

        return self::SUCCESS;
    }
}
