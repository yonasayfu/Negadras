<?php

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\ReviewerAssignmentStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('reviewer only sees assigned submissions in the reviewer queue', function () {
    $this->seed(RolePermissionSeeder::class);

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');
    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $otherReviewerUser = User::factory()->create();
    $otherReviewerUser->assignRole('Reviewer');
    $otherReviewer = Reviewer::factory()->create([
        'user_id' => $otherReviewerUser->id,
    ]);

    $ownedSubmission = Submission::factory()->create();
    $otherSubmission = Submission::factory()->create();

    ReviewerAssignment::factory()->create([
        'submission_id' => $ownedSubmission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $ownedSubmission->current_stage_id,
        'status' => ReviewerAssignmentStatus::Assigned,
    ]);

    ReviewerAssignment::factory()->create([
        'submission_id' => $otherSubmission->id,
        'reviewer_id' => $otherReviewer->id,
        'stage_id' => $otherSubmission->current_stage_id,
        'status' => ReviewerAssignmentStatus::Assigned,
    ]);

    $this->actingAs($reviewerUser)
        ->get(route('reviewer-queue.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reviewers/Queue')
            ->has('assignments', 1)
            ->where('assignments.0.submissionId', $ownedSubmission->id),
        );
});

test('reviewer cannot open another reviewers assignment detail', function () {
    $this->seed(RolePermissionSeeder::class);

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');
    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $otherReviewerUser = User::factory()->create();
    $otherReviewerUser->assignRole('Reviewer');
    $otherReviewer = Reviewer::factory()->create([
        'user_id' => $otherReviewerUser->id,
    ]);

    $submission = Submission::factory()->create();

    $assignment = ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $otherReviewer->id,
        'stage_id' => $submission->current_stage_id,
    ]);

    ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $submission->current_stage_id,
    ]);

    $this->actingAs($reviewerUser)
        ->get(route('reviewer-queue.show', $assignment))
        ->assertForbidden();
});
