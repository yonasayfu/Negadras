<?php

use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can inspect submission version history from the admin detail page', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $submission = Submission::factory()->submitted()->create([
        'title' => 'Admin visible submission',
    ]);

    $version = $submission->versions()->create([
        'version_no' => 1,
        'snapshot_json' => [
            'title' => 'Admin visible submission',
            'status' => SubmissionStatus::Submitted->value,
        ],
        'change_note' => 'Initial final submission.',
        'created_by' => $manager->id,
        'is_locked' => true,
    ]);

    $submission->update([
        'current_version_id' => $version->id,
    ]);

    $this->actingAs($manager)
        ->get(route('admin-submissions.show', $submission))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Submissions/Show')
            ->where('submission.currentVersionNumber', 1)
            ->where('submission.versionCount', 1)
            ->has('submission.versionHistory', 1)
            ->where('submission.versionHistory.0.changeNote', 'Initial final submission.'),
        );
});
