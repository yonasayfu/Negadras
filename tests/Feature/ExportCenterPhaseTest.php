<?php

use App\Models\ExportJob;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can view export center with negadras export resources', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $this->actingAs($manager)
        ->get(route('exports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('exports/Index')
            ->has('resources', 5)
            ->where('resources.0.key', 'submissions-csv'),
        );
});

test('manager can export submissions and create an export job record', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    Submission::factory()->create([
        'title' => 'Exported Submission',
    ]);

    $this->actingAs($manager)
        ->get(route('exports.submissions.csv'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $job = ExportJob::query()->latest('id')->firstOrFail();

    expect($job->type)->toBe('submissions_csv')
        ->and($job->requested_by)->toBe($manager->id)
        ->and($job->row_count)->toBe(1);
});
