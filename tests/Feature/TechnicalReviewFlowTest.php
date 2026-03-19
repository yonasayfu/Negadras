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

test('technical reviewer can save a draft review and later submit it', function () {
    $this->seed(RolePermissionSeeder::class);

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');

    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Shortlisted,
    ]);

    $assignment = ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $submission->current_stage_id,
        'assignment_type' => ReviewAssignmentType::Technical,
        'status' => ReviewerAssignmentStatus::Assigned,
    ]);

    $this->actingAs($reviewerUser)
        ->post(route('technical-reviews.store', $assignment), [
            'intent' => 'draft',
            'innovation_score_optional' => 80,
            'feasibility_score_optional' => 77,
            'execution_score_optional' => 74,
            'market_score_optional' => 82,
            'strengths' => 'Strong product insight.',
            'weaknesses' => 'Execution team still small.',
            'risk_note' => 'Pilot dependency risk.',
            'recommendation' => TechnicalReviewRecommendation::Advance->value,
        ])
        ->assertRedirect(route('technical-reviewer-queue.show', $assignment));

    $review = $assignment->technicalReview()->firstOrFail();

    expect($review->submitted_at)->toBeNull()
        ->and($assignment->fresh()->status)->toBe(ReviewerAssignmentStatus::InProgress);

    $this->actingAs($reviewerUser)
        ->post(route('technical-reviews.store', $assignment), [
            'intent' => 'submit',
            'innovation_score_optional' => 85,
            'feasibility_score_optional' => 79,
            'execution_score_optional' => 76,
            'market_score_optional' => 88,
            'strengths' => 'Ready for the next stage with credible traction.',
            'weaknesses' => 'Operations capacity still needs reinforcement.',
            'risk_note' => 'Scale risk remains moderate.',
            'recommendation' => TechnicalReviewRecommendation::Advance->value,
        ])
        ->assertRedirect(route('technical-reviewer-queue.show', $assignment));

    expect($review->fresh()->submitted_at)->not()->toBeNull()
        ->and($assignment->fresh()->status)->toBe(ReviewerAssignmentStatus::Submitted)
        ->and($review->fresh()->recommendation)->toBe(TechnicalReviewRecommendation::Advance);
});

test('submitted technical review cannot be changed again by the reviewer', function () {
    $this->seed(RolePermissionSeeder::class);

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');

    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Shortlisted,
    ]);

    $assignment = ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $submission->current_stage_id,
        'assignment_type' => ReviewAssignmentType::Technical,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);

    $assignment->technicalReview()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $reviewer->id,
        'stage_id' => $submission->current_stage_id,
        'innovation_score_optional' => 80,
        'feasibility_score_optional' => 70,
        'execution_score_optional' => 68,
        'market_score_optional' => 73,
        'strengths' => 'Already submitted strengths.',
        'weaknesses' => 'Already submitted weaknesses.',
        'risk_note' => 'Already submitted risk.',
        'recommendation' => TechnicalReviewRecommendation::NeedsMoreReview,
        'submitted_at' => now(),
    ]);

    $this->actingAs($reviewerUser)
        ->post(route('technical-reviews.store', $assignment), [
            'intent' => 'draft',
            'innovation_score_optional' => 60,
            'feasibility_score_optional' => 60,
            'execution_score_optional' => 60,
            'market_score_optional' => 60,
            'strengths' => 'Attempted overwrite.',
            'weaknesses' => 'Attempted overwrite.',
            'risk_note' => 'Attempted overwrite.',
            'recommendation' => TechnicalReviewRecommendation::Reject->value,
        ])
        ->assertSessionHas('error');
});
