<?php

use App\Models\ExportJob;
use App\Models\NotificationLog;
use App\Models\OverrideEvent;
use App\Models\Season;
use App\Models\Submission;
use App\Models\User;
use App\OverrideEventType;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can view negadras reports dashboard', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $season = Season::factory()->create([
        'name' => 'Negadras Season 2026',
    ]);

    Submission::factory()->count(2)->create([
        'season_id' => $season->id,
    ]);

    $this->actingAs($manager)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/Index')
            ->where('summary.totalSubmissions', 2)
            ->where('seasonBreakdown.0.name', 'Negadras Season 2026'),
        );
});

test('manager can view governance dashboard with override and delivery data', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $submission = Submission::factory()->create([
        'title' => 'Governance Candidate',
    ]);

    ExportJob::factory()->create([
        'requested_by' => $manager->id,
        'type' => 'submissions_csv',
    ]);

    NotificationLog::factory()->create([
        'recipient_user_id' => $manager->id,
        'sent_by' => $manager->id,
        'title' => 'Governance reminder',
        'category' => 'governance',
    ]);

    OverrideEvent::factory()->create([
        'actor_id' => $manager->id,
        'submission_id' => $submission->id,
        'event_type' => OverrideEventType::RankingOverride,
        'reason' => 'Manual board correction',
    ]);

    $this->actingAs($manager)
        ->get(route('governance.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('governance/Index')
            ->where('summary.overrideEvents', 1)
            ->where('summary.notificationLogs', 1)
            ->where('overrideEvents.0.submissionTitle', 'Governance Candidate')
            ->where('notificationLogs.0.title', 'Governance reminder'),
        );
});
