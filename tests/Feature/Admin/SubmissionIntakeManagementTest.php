<?php

use App\Models\Applicant;
use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('secretary can access the intake queue and sees checklist data', function () {
    $this->seed(RolePermissionSeeder::class);

    $secretary = User::factory()->create();
    $secretary->assignRole('Secretary');

    $submission = Submission::factory()->submitted()->create();

    $submission->statusHistory()->create([
        'from_status' => null,
        'to_status' => SubmissionStatus::Submitted,
        'changed_by' => $secretary->id,
        'reason' => 'Initial final submission.',
        'created_at' => now(),
    ]);

    $this->actingAs($secretary)
        ->get(route('admin-submissions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Submissions/Index')
            ->has('submissions.data', 1)
            ->has('submissions.data.0.intakeChecklist.items', 6)
            ->has('seasonOptions')
            ->has('stageOptions')
            ->has('industryOptions')
            ->has('statusOptions'),
        );
});

test('intake queue filters by status and checklist can become ready', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $completeApplicant = Applicant::factory()->create([
        'phone' => '0911223344',
    ]);
    $completeSubmission = Submission::factory()->create([
        'applicant_id' => $completeApplicant->id,
        'organization_id' => null,
        'summary' => 'Ready summary',
        'problem_statement' => 'Ready problem statement',
        'solution_description' => 'Ready solution description',
        'business_model' => 'Ready business model',
        'status' => SubmissionStatus::UnderIntakeCheck,
    ]);

    $completeSubmission->files()->createMany([
        [
            'submission_version_id' => null,
            'file_type' => 'application_pdf',
            'disk' => 'local',
            'original_name' => 'application.pdf',
            'file_path' => 'negadras/test/application.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1000,
            'uploaded_by' => $manager->id,
            'uploaded_at' => now(),
            'is_required' => true,
            'is_verified' => false,
        ],
        [
            'submission_version_id' => null,
            'file_type' => 'pitch_deck',
            'disk' => 'local',
            'original_name' => 'pitch.pdf',
            'file_path' => 'negadras/test/pitch.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1200,
            'uploaded_by' => $manager->id,
            'uploaded_at' => now(),
            'is_required' => true,
            'is_verified' => false,
        ],
    ]);

    $incompleteSubmission = Submission::factory()->submitted()->create();

    $this->actingAs($manager)
        ->get(route('admin-submissions.index', [
            'status' => SubmissionStatus::UnderIntakeCheck->value,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Submissions/Index')
            ->has('submissions.data', 1)
            ->where('submissions.data.0.id', $completeSubmission->id)
            ->where('submissions.data.0.intakeChecklist.isReady', true),
        );

    expect($incompleteSubmission->id)->not->toBe($completeSubmission->id);
});

test('manager can apply a quick intake transition with a return note', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $submission = Submission::factory()->submitted()->create();

    $submission->statusHistory()->create([
        'from_status' => null,
        'to_status' => SubmissionStatus::Submitted,
        'changed_by' => $manager->id,
        'reason' => 'Initial final submission.',
        'created_at' => now()->subHour(),
    ]);

    $this->actingAs($manager)
        ->post(route('admin-submissions.transition', $submission), [
            'status' => SubmissionStatus::IncompleteReturned->value,
            'reason' => 'Missing registration deck.',
        ])
        ->assertRedirect(route('admin-submissions.show', $submission));

    $submission->refresh()->load('statusHistory');

    expect($submission->status)->toBe(SubmissionStatus::IncompleteReturned)
        ->and($submission->statusHistory->first()->reason)->toBe('Missing registration deck.');
});
