<?php

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\ReviewerAssignmentStatus;
use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can view the screening queue and filter by recommendation', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');

    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $passSubmission = Submission::factory()->create([
        'status' => SubmissionStatus::Eligible,
    ]);
    $rejectSubmission = Submission::factory()->create([
        'status' => SubmissionStatus::Eligible,
    ]);

    $passAssignment = ReviewerAssignment::factory()->create([
        'submission_id' => $passSubmission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $passSubmission->current_stage_id,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);

    $rejectAssignment = ReviewerAssignment::factory()->create([
        'submission_id' => $rejectSubmission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $rejectSubmission->current_stage_id,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);

    $passAssignment->screeningReview()->create([
        'submission_id' => $passSubmission->id,
        'eligibility_status' => ScreeningEligibilityStatus::Eligible,
        'recommendation' => ScreeningRecommendation::Pass,
        'score_optional' => 89,
        'notes' => 'Ready for shortlist discussion.',
        'submitted_at' => now(),
    ]);

    $rejectAssignment->screeningReview()->create([
        'submission_id' => $rejectSubmission->id,
        'eligibility_status' => ScreeningEligibilityStatus::Ineligible,
        'recommendation' => ScreeningRecommendation::Reject,
        'score_optional' => 31,
        'notes' => 'Does not meet the minimum standard.',
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->get(route('screening-queue.index', ['recommendation' => ScreeningRecommendation::Pass->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Screening/Index')
            ->has('submissions', 1)
            ->where('submissions.0.id', $passSubmission->id)
            ->where('submissions.0.latestRecommendation', ScreeningRecommendation::Pass->value),
        );
});

test('manager can open screening detail and review submitted screening data', function () {
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

    $assignment = ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $submission->current_stage_id,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);

    $assignment->screeningReview()->create([
        'submission_id' => $submission->id,
        'eligibility_status' => ScreeningEligibilityStatus::Eligible,
        'recommendation' => ScreeningRecommendation::Escalate,
        'score_optional' => 76,
        'notes' => 'Worth discussing with a manager before the final outcome.',
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->get(route('screening-queue.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Screening/Show')
            ->where('submission.id', $submission->id)
            ->has('submission.reviewerAssignments', 1)
            ->where('submission.reviewerAssignments.0.review.recommendation', ScreeningRecommendation::Escalate->value)
            ->has('decisionOptions'),
        );
});
