<?php

use App\Models\Reviewer;
use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;

test('admin can create a reviewer profile and assign an eligible submission', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $reviewerUser = User::factory()->create();
    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Eligible,
    ]);

    $this->actingAs($admin)
        ->post(route('reviewers.store'), [
            'user_id' => $reviewerUser->id,
            'professional_title' => 'Investment analyst',
            'organization' => 'Negadras Partner',
            'specialization' => 'Innovation',
            'bio' => 'Experienced first-line reviewer.',
            'is_active' => true,
        ])
        ->assertRedirect();

    $reviewer = Reviewer::query()->firstOrFail();

    expect($reviewer->user_id)->toBe($reviewerUser->id)
        ->and($reviewerUser->fresh()->hasRole('Reviewer'))->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin-submissions.reviewer-assignments.store', $submission), [
            'reviewer_id' => $reviewer->id,
            'due_at' => now()->addDays(5)->toDateTimeString(),
        ])
        ->assertRedirect(route('admin-submissions.show', $submission));

    expect($submission->reviewerAssignments()->count())->toBe(1)
        ->and($submission->reviewerAssignments()->first()?->reviewer_id)->toBe($reviewer->id);
});

test('duplicate active reviewer assignment for the same submission and stage is rejected', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $reviewerUser = User::factory()->create();
    $reviewerUser->assignRole('Reviewer');

    $reviewer = Reviewer::factory()->create([
        'user_id' => $reviewerUser->id,
    ]);

    $submission = Submission::factory()->create([
        'status' => SubmissionStatus::Eligible,
    ]);

    $this->actingAs($manager)
        ->post(route('admin-submissions.reviewer-assignments.store', $submission), [
            'reviewer_id' => $reviewer->id,
            'due_at' => now()->addDays(5)->toDateTimeString(),
        ])
        ->assertRedirect();

    $this->actingAs($manager)
        ->from(route('admin-submissions.show', $submission))
        ->post(route('admin-submissions.reviewer-assignments.store', $submission), [
            'reviewer_id' => $reviewer->id,
            'due_at' => now()->addDays(7)->toDateTimeString(),
        ])
        ->assertSessionHasErrors('reviewer_id');
});
