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

test('reviewer can save a draft screening review and later submit it', function () {
    $this->seed(RolePermissionSeeder::class);

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
        'status' => ReviewerAssignmentStatus::Assigned,
    ]);

    $this->actingAs($reviewerUser)
        ->post(route('screening-reviews.store', $assignment), [
            'intent' => 'draft',
            'eligibility_status' => ScreeningEligibilityStatus::Eligible->value,
            'recommendation' => ScreeningRecommendation::Pass->value,
            'score_optional' => 81,
            'notes' => 'Strong concept, but market validation still needs review.',
        ])
        ->assertRedirect(route('reviewer-queue.show', $assignment));

    $review = $assignment->screeningReview()->firstOrFail();

    expect($review->submitted_at)->toBeNull()
        ->and($assignment->fresh()->status)->toBe(ReviewerAssignmentStatus::InProgress);

    $this->actingAs($reviewerUser)
        ->post(route('screening-reviews.store', $assignment), [
            'intent' => 'submit',
            'eligibility_status' => ScreeningEligibilityStatus::Eligible->value,
            'eligibility_checklist' => [
                'identity_verified' => true,
                'problem_is_clear' => true,
                'solution_is_defined' => true,
                'files_are_complete' => true,
            ],
            'recommendation' => ScreeningRecommendation::Pass->value,
            'score_optional' => 88,
            'notes' => 'Ready for the next step with minor clarification only.',
        ])
        ->assertRedirect(route('reviewer-queue.show', $assignment));

    expect($review->fresh()->submitted_at)->not()->toBeNull()
        ->and($assignment->fresh()->status)->toBe(ReviewerAssignmentStatus::Submitted)
        ->and($review->fresh()->recommendation)->toBe(ScreeningRecommendation::Pass);
});

test('submitted screening review cannot be changed by the reviewer again', function () {
    $this->seed(RolePermissionSeeder::class);

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
        'recommendation' => ScreeningRecommendation::Pass,
        'score_optional' => 90,
        'notes' => 'Already submitted.',
        'submitted_at' => now(),
    ]);

    $this->actingAs($reviewerUser)
        ->post(route('screening-reviews.store', $assignment), [
            'intent' => 'draft',
            'eligibility_status' => ScreeningEligibilityStatus::NeedsClarification->value,
            'recommendation' => ScreeningRecommendation::Escalate->value,
            'score_optional' => 50,
            'notes' => 'Attempt to change locked review.',
        ])
        ->assertSessionHas('error');
});
