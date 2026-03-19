<?php

use App\Models\Applicant;
use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('presenter submission history is recorded for draft creation and final submit', function () {
    $user = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
    ]);

    $submission = Submission::factory()->create([
        'applicant_id' => $applicant->id,
        'status' => SubmissionStatus::Draft,
    ]);

    $submission->statusHistory()->create([
        'from_status' => null,
        'to_status' => SubmissionStatus::Draft,
        'changed_by' => $user->id,
        'reason' => 'Draft created.',
        'created_at' => now()->subHour(),
    ]);

    $this->actingAs($user)
        ->put(route('submissions.update', $submission), [
            'intent' => 'submit',
            'season_id' => $submission->season_id,
            'current_stage_id' => $submission->current_stage_id,
            'industry_id' => $submission->industry_id,
            'organization_id' => null,
            'title' => $submission->title,
            'summary' => 'Submitted summary',
            'problem_statement' => 'Submitted problem statement',
            'solution_description' => 'Submitted solution description',
            'business_model' => 'Submitted business model',
            'is_public_after_approval' => false,
        ])
        ->assertRedirect(route('submissions.show', $submission));

    $submission->refresh()->load('statusHistory');

    expect($submission->status)->toBe(SubmissionStatus::Submitted)
        ->and($submission->statusHistory)->toHaveCount(2)
        ->and($submission->statusHistory->first()->to_status)->toBe(SubmissionStatus::Submitted);
});

test('manager can transition submission through intake workflow with required reason handling', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $submission = Submission::factory()->submitted()->create();

    $submission->statusHistory()->create([
        'from_status' => null,
        'to_status' => SubmissionStatus::Submitted,
        'changed_by' => $manager->id,
        'reason' => 'Initial submission.',
        'created_at' => now()->subHour(),
    ]);

    $this->actingAs($manager)
        ->post(route('admin-submissions.transition', $submission), [
            'status' => SubmissionStatus::IncompleteReturned->value,
            'reason' => '',
        ])
        ->assertSessionHasErrors('reason');

    $this->actingAs($manager)
        ->post(route('admin-submissions.transition', $submission), [
            'status' => SubmissionStatus::UnderIntakeCheck->value,
            'reason' => 'Checklist review started.',
        ])
        ->assertRedirect(route('admin-submissions.show', $submission));

    $this->actingAs($manager)
        ->post(route('admin-submissions.transition', $submission), [
            'status' => SubmissionStatus::Eligible->value,
            'reason' => 'All intake requirements satisfied.',
        ])
        ->assertRedirect(route('admin-submissions.show', $submission));

    $submission->refresh()->load('statusHistory');

    expect($submission->status)->toBe(SubmissionStatus::Eligible)
        ->and($submission->statusHistory->pluck('to_status')->all())->toBe([
            SubmissionStatus::Eligible,
            SubmissionStatus::UnderIntakeCheck,
            SubmissionStatus::Submitted,
        ]);
});

test('submission detail pages expose status timeline data', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $presenter = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $presenter->id,
    ]);

    $submission = Submission::factory()->create([
        'applicant_id' => $applicant->id,
        'status' => SubmissionStatus::IncompleteReturned,
    ]);

    $submission->statusHistory()->createMany([
        [
            'from_status' => null,
            'to_status' => SubmissionStatus::Draft,
            'changed_by' => $presenter->id,
            'reason' => 'Draft created.',
            'created_at' => now()->subDays(2),
        ],
        [
            'from_status' => SubmissionStatus::Draft,
            'to_status' => SubmissionStatus::Submitted,
            'changed_by' => $presenter->id,
            'reason' => 'Initial final submission.',
            'created_at' => now()->subDay(),
        ],
        [
            'from_status' => SubmissionStatus::Submitted,
            'to_status' => SubmissionStatus::IncompleteReturned,
            'changed_by' => $manager->id,
            'reason' => 'Missing required attachment.',
            'created_at' => now(),
        ],
    ]);

    $this->actingAs($presenter)
        ->get(route('submissions.show', $submission))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('submissions/Show')
            ->has('submission.statusTimeline', 3)
            ->where('submission.latestStatusReason', 'Missing required attachment.'),
        );

    $this->actingAs($manager)
        ->get(route('admin-submissions.show', $submission))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Submissions/Show')
            ->has('submission.statusTimeline', 3)
            ->has('availableTransitions')
            ->where('submission.latestStatusReason', 'Missing required attachment.'),
        );
});
