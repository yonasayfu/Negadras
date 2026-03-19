<?php

use App\CompetitionSessionStatus;
use App\Models\CompetitionSession;
use App\Models\Panel;
use App\Models\PanelSubmissionAssignment;
use App\Models\SessionPresenter;
use App\Models\Submission;
use App\Models\User;
use App\SessionAppearanceStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;

test('manager can start advance and reveal a live session', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $panel = Panel::factory()->create();
    $session = CompetitionSession::factory()->create([
        'season_id' => $panel->season_id,
        'stage_id' => $panel->stage_id,
        'panel_id' => $panel->id,
    ]);

    $submissionOne = Submission::factory()->create([
        'season_id' => $panel->season_id,
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Shortlisted,
    ]);
    $submissionTwo = Submission::factory()->create([
        'season_id' => $panel->season_id,
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Shortlisted,
    ]);

    PanelSubmissionAssignment::factory()->create([
        'panel_id' => $panel->id,
        'submission_id' => $submissionOne->id,
    ]);
    PanelSubmissionAssignment::factory()->create([
        'panel_id' => $panel->id,
        'submission_id' => $submissionTwo->id,
    ]);

    $first = SessionPresenter::factory()->create([
        'competition_session_id' => $session->id,
        'submission_id' => $submissionOne->id,
        'order_index' => 1,
    ]);
    $second = SessionPresenter::factory()->create([
        'competition_session_id' => $session->id,
        'submission_id' => $submissionTwo->id,
        'order_index' => 2,
    ]);

    $this->actingAs($manager)
        ->post(route('live-sessions.transition', $session), ['intent' => 'start'])
        ->assertRedirect();

    expect($session->fresh()->status)->toBe(CompetitionSessionStatus::Live)
        ->and($first->fresh()->appearance_status)->toBe(SessionAppearanceStatus::Live);

    $this->actingAs($manager)
        ->post(route('live-sessions.transition', $session), ['intent' => 'advance_presenter'])
        ->assertRedirect();

    expect($first->fresh()->appearance_status)->toBe(SessionAppearanceStatus::Completed)
        ->and($second->fresh()->appearance_status)->toBe(SessionAppearanceStatus::Live);

    $this->actingAs($manager)
        ->post(route('live-sessions.transition', $session), ['intent' => 'reveal_scores'])
        ->assertRedirect();

    expect($session->fresh()->scores_revealed)->toBeTrue()
        ->and($session->snapshot()->exists())->toBeTrue();
});

test('manager can reorder the live presenter queue', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $panel = Panel::factory()->create();
    $session = CompetitionSession::factory()->create([
        'season_id' => $panel->season_id,
        'stage_id' => $panel->stage_id,
        'panel_id' => $panel->id,
    ]);

    $firstSubmission = Submission::factory()->create([
        'season_id' => $panel->season_id,
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Shortlisted,
    ]);
    $secondSubmission = Submission::factory()->create([
        'season_id' => $panel->season_id,
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Shortlisted,
    ]);

    $firstPresenter = SessionPresenter::factory()->create([
        'competition_session_id' => $session->id,
        'submission_id' => $firstSubmission->id,
        'order_index' => 1,
    ]);
    $secondPresenter = SessionPresenter::factory()->create([
        'competition_session_id' => $session->id,
        'submission_id' => $secondSubmission->id,
        'order_index' => 2,
    ]);

    $this->actingAs($manager)
        ->put(route('live-sessions.queue.update', $session), [
            'presenters' => [
                ['id' => $secondPresenter->id, 'order_index' => 1],
                ['id' => $firstPresenter->id, 'order_index' => 2],
            ],
        ])
        ->assertRedirect();

    expect($firstPresenter->fresh()->order_index)->toBe(2)
        ->and($secondPresenter->fresh()->order_index)->toBe(1);
});
