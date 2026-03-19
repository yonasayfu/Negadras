<?php

use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('presenter can upload and replace draft submission files in private storage', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
    ]);
    $submission = Submission::factory()->create([
        'applicant_id' => $applicant->id,
        'status' => SubmissionStatus::Draft,
    ]);

    $this->actingAs($user)
        ->post(route('submission-files.store', $submission), [
            'file_type' => 'application_pdf',
            'description' => 'Initial application PDF.',
            'file' => UploadedFile::fake()->create('application.pdf', 300, 'application/pdf'),
        ])
        ->assertRedirect(route('submissions.edit', $submission));

    $firstFile = $submission->files()->firstOrFail();
    Storage::disk('local')->assertExists($firstFile->file_path);

    $this->actingAs($user)
        ->post(route('submission-files.store', $submission), [
            'file_type' => 'application_pdf',
            'description' => 'Replacement PDF.',
            'file' => UploadedFile::fake()->create('replacement.pdf', 300, 'application/pdf'),
        ])
        ->assertRedirect(route('submissions.edit', $submission));

    expect($submission->fresh()->files()->count())->toBe(1);

    $replacement = $submission->fresh()->files()->firstOrFail();

    expect($replacement->original_name)->toBe('replacement.pdf')
        ->and($replacement->submission_version_id)->toBeNull();

    Storage::disk('local')->assertMissing($firstFile->file_path);
    Storage::disk('local')->assertExists($replacement->file_path);
});

test('final submit binds draft submission files to the new current version', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
    ]);
    $season = Season::factory()->create();
    $stage = Stage::factory()->create([
        'season_id' => $season->id,
    ]);
    $industry = Industry::factory()->create();

    $submission = Submission::factory()->create([
        'applicant_id' => $applicant->id,
        'season_id' => $season->id,
        'current_stage_id' => $stage->id,
        'industry_id' => $industry->id,
        'status' => SubmissionStatus::Draft,
        'title' => 'Version-bound files submission',
        'summary' => 'Draft summary',
        'problem_statement' => 'Draft problem',
        'solution_description' => 'Draft solution',
        'business_model' => 'Draft business model',
    ]);

    $this->actingAs($user)
        ->post(route('submission-files.store', $submission), [
            'file_type' => 'application_pdf',
            'description' => 'Application PDF',
            'file' => UploadedFile::fake()->create('application.pdf', 300, 'application/pdf'),
        ])
        ->assertRedirect(route('submissions.edit', $submission));

    $this->actingAs($user)
        ->put(route('submissions.update', $submission), [
            'intent' => 'submit',
            'season_id' => $season->id,
            'current_stage_id' => $stage->id,
            'industry_id' => $industry->id,
            'organization_id' => null,
            'title' => 'Version-bound files submission',
            'summary' => 'Submitted summary',
            'problem_statement' => 'Submitted problem',
            'solution_description' => 'Submitted solution',
            'business_model' => 'Submitted business model',
            'is_public_after_approval' => false,
        ])
        ->assertRedirect(route('submissions.show', $submission));

    $submission->refresh()->load(['currentVersion', 'files']);

    expect($submission->currentVersion)->not()->toBeNull()
        ->and($submission->files->first()?->submission_version_id)->toBe($submission->current_version_id);
});

test('submission files are private and cannot be downloaded by another presenter', function () {
    Storage::fake('local');

    $owner = User::factory()->create();
    $ownerApplicant = Applicant::factory()->create([
        'user_id' => $owner->id,
    ]);
    $intruder = User::factory()->create();
    Applicant::factory()->create([
        'user_id' => $intruder->id,
    ]);

    $submission = Submission::factory()->create([
        'applicant_id' => $ownerApplicant->id,
        'status' => SubmissionStatus::Draft,
    ]);

    $this->actingAs($owner)
        ->post(route('submission-files.store', $submission), [
            'file_type' => 'application_pdf',
            'description' => 'Private PDF',
            'file' => UploadedFile::fake()->create('private.pdf', 300, 'application/pdf'),
        ]);

    $file = $submission->fresh()->files()->firstOrFail();

    $this->actingAs($intruder)
        ->get(route('submission-files.download', $file))
        ->assertForbidden();
});
