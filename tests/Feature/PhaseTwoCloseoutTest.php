<?php

use App\Models\Applicant;
use App\Models\ReviewDecision;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\ShortlistRecord;
use App\Models\Submission;
use App\Models\User;
use App\ReviewAssignmentType;
use App\ReviewDecisionType;
use App\ReviewerAssignmentStatus;
use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use App\ShortlistApprovalStatus;
use App\SubmissionStatus;
use App\TechnicalReviewRecommendation;
use Database\Seeders\RolePermissionSeeder;

test('screening review submission requires a fully checked eligibility checklist', function () {
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
    ]);

    $this->actingAs($reviewerUser)
        ->from(route('reviewer-queue.show', $assignment))
        ->post(route('screening-reviews.store', $assignment), [
            'intent' => 'submit',
            'eligibility_status' => ScreeningEligibilityStatus::Eligible->value,
            'eligibility_checklist' => [
                'identity_verified' => true,
                'problem_is_clear' => false,
                'solution_is_defined' => true,
                'files_are_complete' => true,
            ],
            'recommendation' => ScreeningRecommendation::Pass->value,
            'score_optional' => 85,
            'notes' => 'Attempted final submission without complete checklist.',
        ])
        ->assertSessionHasErrors('eligibility_checklist');
});

test('manager can bulk assign screening reviewers', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $firstReviewer = Reviewer::factory()->create([
        'user_id' => User::factory()->create()->assignRole('Reviewer')->id,
    ]);
    $secondReviewer = Reviewer::factory()->create([
        'user_id' => User::factory()->create()->assignRole('Reviewer')->id,
    ]);

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Eligible,
    ]);

    $this->actingAs($manager)
        ->post(route('admin-submissions.reviewer-assignments.bulk-store', $submission), [
            'reviewer_ids' => [$firstReviewer->id, $secondReviewer->id],
            'due_at' => now()->addDays(3)->toDateTimeString(),
        ])
        ->assertRedirect(route('admin-submissions.show', $submission));

    expect($submission->reviewerAssignments()->count())->toBe(2);
});

test('manager can reopen a submitted screening review', function () {
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
        'eligibility_checklist' => [
            'identity_verified' => true,
            'problem_is_clear' => true,
            'solution_is_defined' => true,
            'files_are_complete' => true,
        ],
        'recommendation' => ScreeningRecommendation::Pass,
        'score_optional' => 91,
        'notes' => 'Submitted review.',
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post(route('reviewer-assignments.transition', $assignment), [
            'intent' => 'reopen',
            'reason' => 'Manager requested one more screening pass.',
        ])
        ->assertRedirect();

    expect($assignment->fresh()->status)->toBe(ReviewerAssignmentStatus::InProgress)
        ->and($assignment->screeningReview()->firstOrFail()->submitted_at)->toBeNull()
        ->and($reviewerUser->fresh()->notifications)->toHaveCount(1);
});

test('manager can record a technical needs-more-review decision without changing the submission status', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $reviewer = Reviewer::factory()->create([
        'user_id' => User::factory()->create()->assignRole('Reviewer')->id,
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
        'innovation_score_optional' => 81,
        'feasibility_score_optional' => 79,
        'execution_score_optional' => 77,
        'market_score_optional' => 84,
        'strengths' => 'Strong technical base.',
        'weaknesses' => 'Scaling risk remains.',
        'risk_note' => 'Operations risk',
        'recommendation' => TechnicalReviewRecommendation::Advance,
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post(route('technical-queue.decision', $submission), [
            'decision_type' => ReviewDecisionType::NeedsMoreReview->value,
            'reason' => 'A second technical opinion is required.',
        ])
        ->assertRedirect(route('technical-queue.show', $submission));

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Shortlisted)
        ->and(ReviewDecision::query()->where('submission_id', $submission->id)->latest('id')->first()?->decision_type)
        ->toBe(ReviewDecisionType::NeedsMoreReview);
});

test('technical shortlist decision creates a pending shortlist record that can be approved and exported', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $presenter = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $presenter->id,
    ]);

    $reviewer = Reviewer::factory()->create([
        'user_id' => User::factory()->create()->assignRole('Reviewer')->id,
    ]);

    $submission = Submission::factory()->create([
        'applicant_id' => $applicant->id,
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
        'innovation_score_optional' => 89,
        'feasibility_score_optional' => 85,
        'execution_score_optional' => 82,
        'market_score_optional' => 90,
        'strengths' => 'Ready for shortlist.',
        'weaknesses' => 'Minor execution risk.',
        'risk_note' => 'Moderate scale risk',
        'recommendation' => TechnicalReviewRecommendation::Advance,
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post(route('technical-queue.decision', $submission), [
            'decision_type' => ReviewDecisionType::Shortlisted->value,
            'reason' => 'Approved into the phase shortlist.',
        ])
        ->assertRedirect(route('technical-queue.show', $submission));

    $shortlistRecord = ShortlistRecord::query()->where('submission_id', $submission->id)->firstOrFail();

    expect($shortlistRecord->approval_status)->toBe(ShortlistApprovalStatus::Pending);

    $this->actingAs($manager)
        ->put(route('shortlist.update', $shortlistRecord), [
            'rank_order_optional' => 2,
            'notes' => 'Approved shortlist entry.',
            'approval_status' => ShortlistApprovalStatus::Approved->value,
        ])
        ->assertRedirect();

    $response = $this->actingAs($manager)
        ->get(route('shortlist.export'));

    $response->assertOk();

    expect($shortlistRecord->fresh()->approval_status)->toBe(ShortlistApprovalStatus::Approved)
        ->and($shortlistRecord->fresh()->approved_at)->not()->toBeNull()
        ->and($shortlistRecord->fresh()->exported_at)->not()->toBeNull();
});

test('overdue review command expires assignments and notifies the reviewer', function () {
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
        'due_at' => now()->subDay(),
    ]);

    $this->artisan('negadras:notify-overdue-reviews')
        ->expectsOutput('Expired 1 overdue assignments.')
        ->assertExitCode(0);

    expect($assignment->fresh()->status)->toBe(ReviewerAssignmentStatus::Expired)
        ->and($reviewerUser->fresh()->notifications)->not()->toBeEmpty();
});
