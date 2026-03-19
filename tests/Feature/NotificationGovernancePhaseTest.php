<?php

use App\FeedbackVisibilityStatus;
use App\Models\PresenterFeedbackPacket;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\ReviewAssignmentType;
use App\ReviewerAssignmentStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;

test('workflow reminder command records notification logs', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');

    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Eligible,
    ]);

    ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $submission->current_stage_id,
        'assignment_type' => ReviewAssignmentType::Screening,
        'status' => ReviewerAssignmentStatus::Assigned,
        'due_at' => now()->subDay(),
    ]);

    PresenterFeedbackPacket::factory()->create([
        'submission_id' => $submission->id,
        'stage_id' => $submission->current_stage_id,
        'visibility_status' => FeedbackVisibilityStatus::InternalReview,
        'sent_at_optional' => null,
    ]);

    $this->artisan('negadras:send-workflow-reminders')
        ->assertSuccessful();

    expect($reviewerUser->notificationLogs()->count())->toBeGreaterThan(0)
        ->and($manager->notificationLogs()->count())->toBeGreaterThan(0);
});
