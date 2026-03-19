<?php

use App\Models\Judge;
use App\Models\Panel;
use App\Models\PanelSubmissionAssignment;
use App\Models\RubricCriterion;
use App\Models\Submission;
use App\Models\User;
use App\PanelSubmissionAssignmentStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;

test('judge can save draft scores and then submit final scores', function () {
    $this->seed(RolePermissionSeeder::class);

    $judgeUser = User::factory()->create();
    $judgeUser->assignRole('Judge');

    $judge = Judge::factory()->create(['user_id' => $judgeUser->id]);
    $panel = Panel::factory()->create();
    $panel->members()->create([
        'judge_id' => $judge->id,
        'role_in_panel' => 'chair',
        'display_order' => 1,
    ]);
    $criteria = RubricCriterion::factory()->count(2)->create(['rubric_id' => $panel->rubric_id]);

    $submission = Submission::factory()->create([
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Shortlisted,
    ]);

    $assignment = PanelSubmissionAssignment::factory()->create([
        'panel_id' => $panel->id,
        'submission_id' => $submission->id,
        'status' => PanelSubmissionAssignmentStatus::Assigned,
    ]);

    $draftPayload = [
        'intent' => 'draft',
        'scores' => $criteria->map(fn ($criterion) => [
            'criterion_id' => $criterion->id,
            'score_value' => 7,
            'comment' => 'Draft comment',
        ])->all(),
        'private_comment' => 'Internal observation',
        'presenter_comment' => 'Visible summary',
    ];

    $this->actingAs($judgeUser)
        ->post(route('judge-workspace.scores.store', $assignment), $draftPayload)
        ->assertRedirect(route('judge-workspace.show', $assignment));

    expect($assignment->fresh()->status)->toBe(PanelSubmissionAssignmentStatus::Scoring)
        ->and($assignment->scoreEntries()->count())->toBe(2);

    $this->actingAs($judgeUser)
        ->post(route('judge-workspace.scores.store', $assignment), [
            ...$draftPayload,
            'intent' => 'submit',
            'scores' => $criteria->map(fn ($criterion) => [
                'criterion_id' => $criterion->id,
                'score_value' => 9,
                'comment' => 'Final comment',
            ])->all(),
        ])
        ->assertRedirect(route('judge-workspace.show', $assignment));

    expect($assignment->fresh()->status)->toBe(PanelSubmissionAssignmentStatus::Submitted)
        ->and($assignment->scoreEntries()->whereNotNull('submitted_at')->count())->toBe(2);
});

test('manager can lock a scoring assignment and the judge can no longer change it', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $judgeUser = User::factory()->create();
    $judgeUser->assignRole('Judge');

    $judge = Judge::factory()->create(['user_id' => $judgeUser->id]);
    $panel = Panel::factory()->create();
    $panel->members()->create([
        'judge_id' => $judge->id,
        'role_in_panel' => 'chair',
        'display_order' => 1,
    ]);
    $criterion = RubricCriterion::factory()->create(['rubric_id' => $panel->rubric_id]);

    $assignment = PanelSubmissionAssignment::factory()->create([
        'panel_id' => $panel->id,
        'submission_id' => Submission::factory()->create(['current_stage_id' => $panel->stage_id, 'status' => SubmissionStatus::Shortlisted])->id,
        'status' => PanelSubmissionAssignmentStatus::Scoring,
    ]);

    $this->actingAs($manager)
        ->put(route('panel-scoring.lock.update', $assignment), [
            'intent' => 'lock',
            'reason' => 'Final moderation',
        ])
        ->assertRedirect();

    $this->actingAs($judgeUser)
        ->from(route('judge-workspace.show', $assignment))
        ->post(route('judge-workspace.scores.store', $assignment), [
            'intent' => 'draft',
            'scores' => [[
                'criterion_id' => $criterion->id,
                'score_value' => 8,
                'comment' => 'Attempt after lock',
            ]],
            'private_comment' => '',
            'presenter_comment' => '',
        ])
        ->assertSessionHasErrors('scores');
});
