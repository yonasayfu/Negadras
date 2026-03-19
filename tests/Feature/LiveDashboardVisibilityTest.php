<?php

use App\Models\CompetitionSession;
use App\Models\Panel;
use App\Models\SessionPresenter;
use App\Models\Submission;
use App\SessionAppearanceStatus;
use App\SubmissionStatus;
use App\Support\LiveSessionCoordinator;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('live dashboard snapshot hides score visibility until reveal', function () {
    $this->seed(RolePermissionSeeder::class);

    $panel = Panel::factory()->create();
    $session = CompetitionSession::factory()->create([
        'season_id' => $panel->season_id,
        'stage_id' => $panel->stage_id,
        'panel_id' => $panel->id,
        'scores_revealed' => false,
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

    app(LiveSessionCoordinator::class)->refreshSnapshot($session);

    $this->get(route('live-dashboard.show', $session))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('live-dashboard/Show')
            ->where('snapshot.currentPresenter.isScoreVisible', false),
        );
});
