<?php

use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;
use Inertia\Testing\AssertableInertia as Assert;

test('final submission creates the first locked version snapshot and marks it current', function () {
    $user = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
    ]);
    $season = Season::factory()->create();
    $stage = Stage::factory()->create([
        'season_id' => $season->id,
    ]);
    $industry = Industry::factory()->create();

    $this->actingAs($user)
        ->post(route('submissions.store'), [
            'intent' => 'submit',
            'season_id' => $season->id,
            'current_stage_id' => $stage->id,
            'industry_id' => $industry->id,
            'organization_id' => null,
            'title' => 'Versioned Negadras Submission',
            'summary' => 'Summary one',
            'problem_statement' => 'Problem one',
            'solution_description' => 'Solution one',
            'business_model' => 'Business model one',
            'is_public_after_approval' => false,
        ])
        ->assertRedirect();

    $submission = Submission::query()->with(['currentVersion', 'versions'])->firstOrFail();

    expect($submission->status)->toBe(SubmissionStatus::Submitted)
        ->and($submission->versions)->toHaveCount(1)
        ->and($submission->currentVersion)->not()->toBeNull()
        ->and($submission->currentVersion?->version_no)->toBe(1)
        ->and($submission->currentVersion?->is_locked)->toBeTrue()
        ->and($submission->currentVersion?->snapshot_json['title'])->toBe('Versioned Negadras Submission');
});

test('resubmitting a returned submission creates a new version instead of overwriting the old one', function () {
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
        'status' => SubmissionStatus::IncompleteReturned,
        'title' => 'Returned submission',
        'summary' => 'Old summary',
        'problem_statement' => 'Old problem',
        'solution_description' => 'Old solution',
        'business_model' => 'Old business model',
    ]);

    $firstVersion = $submission->versions()->create([
        'version_no' => 1,
        'snapshot_json' => [
            'title' => 'Returned submission',
            'status' => SubmissionStatus::Submitted->value,
        ],
        'change_note' => 'Initial final submission.',
        'created_by' => $user->id,
        'is_locked' => true,
    ]);

    $submission->update([
        'current_version_id' => $firstVersion->id,
    ]);

    $this->actingAs($user)
        ->put(route('submissions.update', $submission), [
            'intent' => 'submit',
            'season_id' => $season->id,
            'current_stage_id' => $stage->id,
            'industry_id' => $industry->id,
            'organization_id' => null,
            'title' => 'Returned submission v2',
            'summary' => 'New summary',
            'problem_statement' => 'New problem',
            'solution_description' => 'New solution',
            'business_model' => 'New business model',
            'is_public_after_approval' => true,
        ])
        ->assertRedirect(route('submissions.show', $submission));

    $submission->refresh()->load(['currentVersion', 'versions']);

    expect($submission->versions)->toHaveCount(2)
        ->and($submission->currentVersion?->version_no)->toBe(2)
        ->and($submission->currentVersion?->change_note)->toBe('Presenter resubmitted after correction.')
        ->and($submission->versions->pluck('version_no')->all())->toBe([2, 1]);
});

test('submission detail page shows the current version label and history', function () {
    $user = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
    ]);

    $submission = Submission::factory()->submitted()->create([
        'applicant_id' => $applicant->id,
        'title' => 'Visible history submission',
    ]);

    $version = $submission->versions()->create([
        'version_no' => 1,
        'snapshot_json' => [
            'title' => 'Visible history submission',
            'status' => SubmissionStatus::Submitted->value,
        ],
        'change_note' => 'Initial final submission.',
        'created_by' => $user->id,
        'is_locked' => true,
    ]);

    $submission->update([
        'current_version_id' => $version->id,
    ]);

    $this->actingAs($user)
        ->get(route('submissions.show', $submission))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('submissions/Show')
            ->where('submission.currentVersionNumber', 1)
            ->where('submission.versionCount', 1)
            ->has('submission.versionHistory', 1)
            ->where('submission.versionHistory.0.versionNo', 1)
            ->where('submission.versionHistory.0.isCurrent', true),
        );
});
