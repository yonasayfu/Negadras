<?php

use App\Models\ActivityLog;
use App\Models\ImportRun;
use App\Models\Media;
use App\Models\Page;
use App\Models\Season;
use App\Models\Submission;
use App\Models\User;
use App\SeasonStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager sees Negadras operations metrics on the dashboard', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    Season::factory()->create([
        'status' => SeasonStatus::Active,
    ]);
    Page::factory()->count(3)->create();
    Media::factory()->count(2)->create(['uploaded_by' => $manager->id]);
    ImportRun::factory()->create(['status' => 'completed']);
    ActivityLog::factory()->count(2)->create();
    Submission::factory()->create(['status' => SubmissionStatus::Submitted]);
    Submission::factory()->create(['status' => SubmissionStatus::IncompleteReturned]);

    $this->actingAs($manager)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('operations.metrics', 4)
            ->has('operations.submissionBreakdown', 6)
            ->has('recentActivity', 2)
            ->where('currentSeason.statusLabel', 'Active')
            ->where('operations.metrics.0.value', 2)
            ->where('operations.metrics.1.value', 1)
            ->where('platformHealth.2.value', 1)
            ->where('auth.can.viewReports', true)
            ->where('auth.can.managePages', true),
        );
});
