<?php

use App\ConflictOfInterestStatus;
use App\Models\ConflictOfInterestDeclaration;
use App\Models\Judge;
use App\Models\Panel;
use App\Models\PanelSubmissionAssignment;
use App\Models\Submission;
use App\Models\User;
use App\PanelSubmissionAssignmentStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;

test('judge can declare a conflict and manager can resolve it', function () {
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

    $assignment = PanelSubmissionAssignment::factory()->create([
        'panel_id' => $panel->id,
        'submission_id' => Submission::factory()->create(['current_stage_id' => $panel->stage_id, 'status' => SubmissionStatus::Shortlisted])->id,
        'status' => PanelSubmissionAssignmentStatus::Assigned,
    ]);

    $this->actingAs($judgeUser)
        ->post(route('judge-workspace.conflicts.store', $assignment), [
            'conflict_type' => 'self_declared',
            'description' => 'Prior advisory engagement with the startup.',
        ])
        ->assertRedirect(route('judge-workspace.show', $assignment));

    $declaration = ConflictOfInterestDeclaration::query()->firstOrFail();

    expect($declaration->status)->toBe(ConflictOfInterestStatus::Active);

    $this->actingAs($manager)
        ->put(route('panel-scoring.conflicts.update', $declaration), [
            'status' => ConflictOfInterestStatus::Resolved->value,
            'admin_note' => 'Approved reassignment and cleared for archive.',
        ])
        ->assertRedirect();

    expect($declaration->fresh()->status)->toBe(ConflictOfInterestStatus::Resolved);
});
