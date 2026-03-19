<?php

use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\User;
use App\ReviewAssignmentType;
use App\ReviewerAssignmentStatus;
use App\ScreeningEligibilityStatus;
use App\ScreeningRecommendation;
use App\SubmissionStatus;
use App\TechnicalReviewRecommendation;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can compare screening and technical reviews on technical detail page', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $screeningReviewer = Reviewer::factory()->create([
        'user_id' => User::factory()->create()->assignRole('Reviewer')->id,
    ]);
    $technicalReviewer = Reviewer::factory()->create([
        'user_id' => User::factory()->create()->assignRole('Reviewer')->id,
    ]);

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Shortlisted,
    ]);

    $screeningAssignment = ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $screeningReviewer->id,
        'stage_id' => $submission->current_stage_id,
        'assignment_type' => ReviewAssignmentType::Screening,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);

    $technicalAssignment = ReviewerAssignment::factory()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $technicalReviewer->id,
        'stage_id' => $submission->current_stage_id,
        'assignment_type' => ReviewAssignmentType::Technical,
        'status' => ReviewerAssignmentStatus::Submitted,
    ]);

    $screeningAssignment->screeningReview()->create([
        'submission_id' => $submission->id,
        'eligibility_status' => ScreeningEligibilityStatus::Eligible,
        'recommendation' => ScreeningRecommendation::Pass,
        'score_optional' => 82,
        'notes' => 'Strong shortlist candidate.',
        'submitted_at' => now(),
    ]);

    $technicalAssignment->technicalReview()->create([
        'submission_id' => $submission->id,
        'reviewer_id' => $technicalReviewer->id,
        'stage_id' => $submission->current_stage_id,
        'innovation_score_optional' => 85,
        'feasibility_score_optional' => 78,
        'execution_score_optional' => 73,
        'market_score_optional' => 81,
        'strengths' => 'Clear differentiation and believable adoption path.',
        'weaknesses' => 'Execution bandwidth is still thin.',
        'risk_note' => 'Operational scale-up risk.',
        'recommendation' => TechnicalReviewRecommendation::Advance,
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->get(route('technical-queue.show', $submission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Technical/Show')
            ->where('submission.id', $submission->id)
            ->has('submission.screeningReviews', 1)
            ->has('submission.technicalAssignments', 1)
            ->where('submission.technicalAssignments.0.review.recommendation', TechnicalReviewRecommendation::Advance->value)
            ->where('submission.averageTechnicalScore', 79.3),
        );
});
