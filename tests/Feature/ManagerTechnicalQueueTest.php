<?php

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\ReviewAssignmentType;
use App\ReviewerAssignmentStatus;
use App\SubmissionStatus;
use App\TechnicalReviewRecommendation;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can assign a technical reviewer to a shortlisted submission', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');

    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Shortlisted,
    ]);

    $this->actingAs($manager)
        ->post(route('admin-submissions.technical-reviewer-assignments.store', $submission), [
            'reviewer_id' => $reviewer->id,
            'due_at' => now()->addDays(4)->toDateTimeString(),
        ])
        ->assertRedirect(route('technical-queue.show', $submission));

    $assignment = $submission->reviewerAssignments()->firstOrFail();

    expect($assignment->assignment_type)->toBe(ReviewAssignmentType::Technical)
        ->and($assignment->reviewer_id)->toBe($reviewer->id);
});

test('manager can view technical queue filtered by recommendation', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $reviewer = Reviewer::factory()->create([
        'user_id' => User::factory()->create()->assignRole('Reviewer')->id,
    ]);

    $advanceSubmission = Submission::factory()->create([
        'status' => SubmissionStatus::Shortlisted,
    ]);
    $rejectSubmission = Submission::factory()->create([
        'status' => SubmissionStatus::Shortlisted,
    ]);

    $advanceAssignment = ReviewerAssignment::factory()->create([
        'submission_id' => $advanceSubmission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $advanceSubmission->current_stage_id,
        'assignment_type' => ReviewAssignmentType::Technical,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);
    $rejectAssignment = ReviewerAssignment::factory()->create([
        'submission_id' => $rejectSubmission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $rejectSubmission->current_stage_id,
        'assignment_type' => ReviewAssignmentType::Technical,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);

    $advanceAssignment->technicalReview()->create([
        'submission_id' => $advanceSubmission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $advanceSubmission->current_stage_id,
        'recommendation' => TechnicalReviewRecommendation::Advance,
        'strengths' => 'Advance strengths',
        'weaknesses' => 'Advance weaknesses',
        'risk_note' => 'Advance risk',
        'submitted_at' => now(),
    ]);

    $rejectAssignment->technicalReview()->create([
        'submission_id' => $rejectSubmission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $rejectSubmission->current_stage_id,
        'recommendation' => TechnicalReviewRecommendation::Reject,
        'strengths' => 'Reject strengths',
        'weaknesses' => 'Reject weaknesses',
        'risk_note' => 'Reject risk',
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->get(route('technical-queue.index', ['recommendation' => TechnicalReviewRecommendation::Advance->value]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Technical/Index')
            ->has('submissions', 1)
            ->where('submissions.0.id', $advanceSubmission->id)
            ->where('submissions.0.latestTechnicalRecommendationLabel', TechnicalReviewRecommendation::Advance->label()),
        );
});
