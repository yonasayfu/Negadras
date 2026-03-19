<?php

use App\Models\Applicant;
use App\Models\Submission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can inspect admin submission pages', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $submission = Submission::factory()->create();
    $submission->load('applicant.user');

    $this->actingAs($manager)
        ->get(route('admin-submissions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Submissions/Index')
            ->has('submissions.data', 1),
        );

    $this->actingAs($manager)
        ->get(route('admin-submissions.show', $submission))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Submissions/Show')
            ->where('submission.id', $submission->id)
            ->where('submission.title', $submission->title),
        );
});

test('member cannot access admin submission pages', function () {
    $this->seed(RolePermissionSeeder::class);

    $member = User::factory()->create();
    $member->assignRole('Member');

    $submission = Submission::factory()->create([
        'applicant_id' => Applicant::factory(),
    ]);

    $this->actingAs($member)
        ->get(route('admin-submissions.index'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('admin-submissions.show', $submission))
        ->assertForbidden();
});
