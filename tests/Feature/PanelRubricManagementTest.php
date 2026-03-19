<?php

use App\Models\Judge;
use App\Models\Panel;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\Models\User;
use App\PanelStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can create a rubric and a panel with one chair', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $judge = Judge::factory()->create();
    $season = Season::factory()->create();
    $stage = Stage::factory()->create(['season_id' => $season->id]);

    $this->actingAs($manager)
        ->post(route('rubrics.store'), [
            'name' => 'Final judging rubric',
            'description' => 'Weighted final panel rubric.',
            'is_active' => true,
            'stage_ids' => [$stage->id],
            'industry_ids' => [],
            'criteria' => [
                [
                    'name' => 'Innovation',
                    'description' => 'Novelty and differentiation.',
                    'max_score' => 10,
                    'weight' => 40,
                    'order_index' => 1,
                    'is_required' => true,
                    'visibility_rule' => null,
                    'help_text' => null,
                ],
                [
                    'name' => 'Execution',
                    'description' => 'Team and delivery readiness.',
                    'max_score' => 10,
                    'weight' => 60,
                    'order_index' => 2,
                    'is_required' => true,
                    'visibility_rule' => null,
                    'help_text' => null,
                ],
            ],
        ])
        ->assertRedirect();

    $rubric = Rubric::query()->firstOrFail();

    $this->actingAs($manager)
        ->post(route('panels.store'), [
            'season_id' => $season->id,
            'stage_id' => $stage->id,
            'rubric_id' => $rubric->id,
            'name' => 'Stage A Panel',
            'description' => 'Core judges',
            'status' => PanelStatus::Active->value,
            'members' => [
                [
                    'judge_id' => $judge->id,
                    'role_in_panel' => 'chair',
                    'display_order' => 1,
                ],
            ],
        ])
        ->assertRedirect();

    $panel = Panel::query()->firstOrFail();

    expect($panel->rubric_id)->toBe($rubric->id)
        ->and($panel->members()->count())->toBe(1);
});

test('manager can assign an eligible submission to a panel and inspect the panel page', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $panel = Panel::factory()->create();
    $judge = Judge::factory()->create();
    $panel->members()->create([
        'judge_id' => $judge->id,
        'role_in_panel' => 'chair',
        'display_order' => 1,
    ]);
    RubricCriterion::factory()->count(2)->create(['rubric_id' => $panel->rubric_id]);

    $submission = Submission::factory()->create([
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Eligible,
    ]);

    $this->actingAs($manager)
        ->post(route('panel-scoring.assignments.store', $panel), [
            'submission_id' => $submission->id,
        ])
        ->assertRedirect(route('panels.show', $panel));

    $this->actingAs($manager)
        ->get(route('panels.show', $panel))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Panels/Show')
            ->where('panel.id', $panel->id)
            ->has('panel.assignments', 1),
        );
});
