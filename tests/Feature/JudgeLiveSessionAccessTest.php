<?php

use App\Models\CompetitionSession;
use App\Models\Judge;
use App\Models\Panel;
use App\Models\PanelMember;
use App\Models\PanelSubmissionAssignment;
use App\Models\SessionPresenter;
use App\Models\Submission;
use App\Models\User;
use App\SessionAppearanceStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;

test('judge can only access live sessions for panels they belong to', function () {
    $this->seed(RolePermissionSeeder::class);

    $judgeUser = User::factory()->create();
    $judgeUser->assignRole('Judge');
    $judge = Judge::factory()->create(['user_id' => $judgeUser->id]);

    $otherUser = User::factory()->create();
    $otherUser->assignRole('Judge');
    $otherJudge = Judge::factory()->create(['user_id' => $otherUser->id]);

    $panel = Panel::factory()->create();
    PanelMember::factory()->create([
        'panel_id' => $panel->id,
        'judge_id' => $judge->id,
    ]);

    $session = CompetitionSession::factory()->create([
        'season_id' => $panel->season_id,
        'stage_id' => $panel->stage_id,
        'panel_id' => $panel->id,
    ]);

    $submission = Submission::factory()->create([
        'season_id' => $panel->season_id,
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Shortlisted,
    ]);

    SessionPresenter::factory()->create([
        'competition_session_id' => $session->id,
        'submission_id' => $submission->id,
        'appearance_status' => SessionAppearanceStatus::Live,
    ]);

    PanelSubmissionAssignment::factory()->create([
        'panel_id' => $panel->id,
        'submission_id' => $submission->id,
        'session_id_optional' => $session->id,
    ]);

    $this->actingAs($judgeUser)
        ->get(route('judge-live.show', $session))
        ->assertSuccessful();

    $this->actingAs($otherUser)
        ->get(route('judge-live.show', $session))
        ->assertForbidden();
});
