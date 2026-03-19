<?php

use App\AwardType;
use App\Models\Judge;
use App\Models\Panel;
use App\Models\PanelMember;
use App\Models\PanelSubmissionAssignment;
use App\Models\RubricCriterion;
use App\Models\ScoreEntry;
use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can generate rankings and record awards from finalized results', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $judgeUser = User::factory()->create();
    $judge = Judge::factory()->create([
        'user_id' => $judgeUser->id,
    ]);

    $panel = Panel::factory()->create();
    $criterion = RubricCriterion::factory()->create([
        'rubric_id' => $panel->rubric_id,
        'max_score' => 10,
        'weight' => 100,
    ]);

    PanelMember::factory()->create([
        'panel_id' => $panel->id,
        'judge_id' => $judge->id,
    ]);

    $submission = Submission::factory()->create([
        'season_id' => $panel->season_id,
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Shortlisted,
    ]);

    $assignment = PanelSubmissionAssignment::factory()->create([
        'panel_id' => $panel->id,
        'submission_id' => $submission->id,
    ]);

    ScoreEntry::factory()->create([
        'submission_id' => $submission->id,
        'panel_submission_assignment_id' => $assignment->id,
        'panel_id_optional' => $panel->id,
        'judge_id' => $judge->id,
        'rubric_criterion_id' => $criterion->id,
        'score_value' => 9,
        'submitted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post(route('rankings.store'), [
            'stage_id' => $panel->stage_id,
            'competition_session_id' => null,
            'finalize' => true,
            'scope' => 'stage',
        ])
        ->assertRedirect(route('rankings.index', [
            'stage_id' => $panel->stage_id,
        ]));

    $snapshot = $submission->rankingSnapshots()->first();

    expect($snapshot)->not->toBeNull()
        ->and($snapshot?->finalized_at)->not->toBeNull();

    $this->actingAs($manager)
        ->post(route('awards.store'), [
            'submission_id' => $submission->id,
            'season_id' => $submission->season_id,
            'ranking_snapshot_id' => $snapshot?->id,
            'award_type' => AwardType::Winner->value,
            'rank_position' => 1,
            'prize_value_optional' => 50000,
            'notes' => 'Overall winner selected after final panel review.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('award_records', [
        'submission_id' => $submission->id,
        'award_type' => AwardType::Winner->value,
    ]);

    $this->actingAs($manager)
        ->get(route('awards.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Awards/Index')
            ->has('awards', 1)
            ->where('awards.0.submissionId', $submission->id)
            ->where('awards.0.awardType', AwardType::Winner->value),
        );
});
