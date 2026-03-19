<?php

use App\FeedbackVisibilityStatus;
use App\JudgeCommentType;
use App\Models\Applicant;
use App\Models\Judge;
use App\Models\JudgeComment;
use App\Models\Panel;
use App\Models\PanelSubmissionAssignment;
use App\Models\ReviewDecision;
use App\Models\Submission;
use App\Models\User;
use App\ReviewDecisionType;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can prepare and send a presenter feedback packet', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $presenterUser = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $presenterUser->id,
        'full_name' => 'Selam Presenter',
    ]);

    $panel = Panel::factory()->create();
    $judge = Judge::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $submission = Submission::factory()->create([
        'season_id' => $panel->season_id,
        'current_stage_id' => $panel->stage_id,
        'applicant_id' => $applicant->id,
        'status' => SubmissionStatus::Shortlisted,
        'title' => 'Phase 5 Feedback Candidate',
    ]);

    $assignment = PanelSubmissionAssignment::factory()->create([
        'panel_id' => $panel->id,
        'submission_id' => $submission->id,
    ]);

    JudgeComment::factory()->create([
        'submission_id' => $submission->id,
        'judge_id' => $judge->id,
        'panel_submission_assignment_id' => $assignment->id,
        'comment_type' => JudgeCommentType::PresenterVisible,
        'content' => 'Strong traction and strong team alignment.',
    ]);

    ReviewDecision::factory()->create([
        'submission_id' => $submission->id,
        'stage_id' => $submission->current_stage_id,
        'decision_type' => ReviewDecisionType::Shortlisted,
        'decision_reason' => 'Move forward to awards and archive preparation.',
        'decided_by' => $manager->id,
        'decided_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post(route('feedback-packets.store'), [
            'submission_id' => $submission->id,
            'visibility_status' => FeedbackVisibilityStatus::InternalReview->value,
            'send_now' => false,
        ])
        ->assertRedirect();

    $packet = $submission->feedbackPackets()->first();

    expect($packet)->not->toBeNull()
        ->and($packet?->visibility_status)->toBe(FeedbackVisibilityStatus::InternalReview);

    $this->actingAs($manager)
        ->post(route('feedback-packets.send', $packet), [
            'send_notification' => false,
        ])
        ->assertRedirect();

    $packet?->refresh();

    expect($packet?->visibility_status)->toBe(FeedbackVisibilityStatus::PresenterVisible)
        ->and($packet?->sent_at_optional)->not->toBeNull();

    $this->actingAs($presenterUser)
        ->get(route('feedback.show', $packet))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('feedback/Show')
            ->where('packet.submissionTitle', $submission->title)
            ->where('packet.summary', fn (string $summary): bool => str_contains($summary, $submission->title)),
        );
});
