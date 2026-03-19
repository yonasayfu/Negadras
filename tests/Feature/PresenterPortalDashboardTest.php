<?php

use App\Models\Applicant;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\Models\User;
use App\SeasonStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('presenter sees open call visibility and submission counters on the dashboard', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Member');

    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
    ]);

    $season = Season::factory()->create([
        'status' => SeasonStatus::Active,
        'registration_open_at' => now()->subDay(),
        'registration_close_at' => now()->addDays(7),
    ]);

    $stage = Stage::factory()->create([
        'season_id' => $season->id,
    ]);

    Submission::factory()->create([
        'applicant_id' => $applicant->id,
        'season_id' => $season->id,
        'current_stage_id' => $stage->id,
        'status' => SubmissionStatus::Draft,
    ]);

    Submission::factory()->create([
        'applicant_id' => $applicant->id,
        'season_id' => $season->id,
        'current_stage_id' => $stage->id,
        'status' => SubmissionStatus::Submitted,
    ]);

    Submission::factory()->create([
        'applicant_id' => $applicant->id,
        'season_id' => $season->id,
        'current_stage_id' => $stage->id,
        'status' => SubmissionStatus::IncompleteReturned,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('currentSeason.name', $season->name)
            ->where('currentSeason.isOpenForApplications', true)
            ->where('presenterPortal.hasApplicantProfile', true)
            ->where('presenterPortal.canCreateSubmission', true)
            ->where('presenterPortal.counts.draft', 1)
            ->where('presenterPortal.counts.submitted', 1)
            ->where('presenterPortal.counts.returned', 1)
            ->where('presenterPortal.counts.total', 3)
            ->has('presenterPortal.recentSubmissions', 3),
        );
});
