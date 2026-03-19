<?php

use App\Models\Applicant;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\ReviewerAssignmentStatus;
use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;

test('manager can reassign a reviewer from the screening workflow', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $firstReviewerUser = User::factory()->create();
    $firstReviewerUser->assignRole('Reviewer');
    $firstReviewer = Reviewer::factory()->create([
        'user_id' => $firstReviewerUser->id,
    ]);

    $secondReviewerUser = User::factory()->create();
    $secondReviewerUser->assignRole('Reviewer');
    $secondReviewer = Reviewer::factory()->create([
        'user_id' => $secondReviewerUser->id,
    ]);

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Eligible,
    ]);

    $assignment = ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $firstReviewer->id,
        'stage_id' => $submission->current_stage_id,
        'status' => ReviewerAssignmentStatus::Assigned,
    ]);

    $this->actingAs($manager)
        ->put(route('reviewer-assignments.update', $assignment), [
            'reviewer_id' => $secondReviewer->id,
            'due_at' => now()->addDays(4)->toDateTimeString(),
            'reason' => 'Balance the review workload.',
        ])
        ->assertRedirect();

    expect($assignment->fresh()->status)->toBe(ReviewerAssignmentStatus::Cancelled)
        ->and(
            ReviewerAssignment::query()
                ->where('submission_id', $submission->id)
                ->where('reviewer_id', $secondReviewer->id)
                ->exists()
        )->toBeTrue();
});

test('manager can shortlist a submission after a submitted screening review and presenter is notified', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $presenter = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $presenter->id,
    ]);

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');
    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $submission = Submission::factory()->create([
        'applicant_id' => $applicant->id,
        'status' => SubmissionStatus::Eligible,
    ]);

    $assignment = ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $submission->current_stage_id,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);

    $assignment->screeningReview()->create([
        'submission_id' => $submission->id,
        'eligibility_status' => ScreeningEligibilityStatus::Eligible,
        'recommendation' => ScreeningRecommendation::Pass,
        'score_optional' => 93,
        'notes' => 'Strong submission with clear next-stage readiness.',
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post(route('screening-queue.decision', $submission), [
            'status' => SubmissionStatus::Shortlisted->value,
            'reason' => 'Shortlisted after positive screening outcome.',
        ])
        ->assertRedirect(route('screening-queue.show', $submission));

    $submission->refresh();
    $presenter->refresh();

    expect($submission->status)->toBe(SubmissionStatus::Shortlisted)
        ->and($presenter->notifications)->toHaveCount(1)
        ->and($presenter->notifications->first()->data['title'])->toBe('Submission Shortlisted');
});

test('manager cannot apply screening decision before a submitted screening review exists', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Eligible,
    ]);

    $this->actingAs($manager)
        ->from(route('screening-queue.show', $submission))
        ->post(route('screening-queue.decision', $submission), [
            'status' => SubmissionStatus::Shortlisted->value,
            'reason' => 'Attempted without reviewer evidence.',
        ])
        ->assertSessionHasErrors('status');
});
