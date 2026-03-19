<?php

use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Organization;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\Models\User;
use App\SubmissionStatus;
use Inertia\Testing\AssertableInertia as Assert;

test('signed in user without applicant profile can view submissions index and gets redirected to presenter profile before create', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('submissions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('submissions/Index')
            ->where('hasApplicantProfile', false)
            ->where('submissions', []),
        );

    $this->actingAs($user)
        ->get(route('submissions.create'))
        ->assertRedirect(route('applicant-profile.edit'));
});

test('presenter can save a draft submission and later submit it', function () {
    $user = User::factory()->create();
    $applicant = Applicant::factory()->create([
        'user_id' => $user->id,
    ]);
    $season = Season::factory()->create();
    $stage = Stage::factory()->create([
        'season_id' => $season->id,
    ]);
    $industry = Industry::factory()->create();
    $organization = Organization::factory()->create([
        'industry_id' => $industry->id,
    ]);

    $organization->teamMembers()->create([
        'applicant_id' => $applicant->id,
        'full_name' => $applicant->full_name,
        'role_title' => 'Founder',
        'email' => $applicant->email,
        'phone' => $applicant->phone,
        'bio' => 'Primary contact',
        'is_primary_contact' => true,
    ]);

    $this->actingAs($user)
        ->post(route('submissions.store'), [
            'intent' => 'draft',
            'season_id' => $season->id,
            'current_stage_id' => $stage->id,
            'industry_id' => $industry->id,
            'organization_id' => $organization->id,
            'title' => 'Negadras Venture',
            'summary' => '',
            'problem_statement' => '',
            'solution_description' => '',
            'business_model' => '',
            'is_public_after_approval' => true,
        ])
        ->assertRedirect();

    $submission = Submission::query()->firstOrFail();

    expect($submission->applicant_id)->toBe($applicant->id)
        ->and($submission->status)->toBe(SubmissionStatus::Draft)
        ->and($submission->submitted_at)->toBeNull();

    $this->actingAs($user)
        ->put(route('submissions.update', $submission), [
            'intent' => 'submit',
            'season_id' => $season->id,
            'current_stage_id' => $stage->id,
            'industry_id' => $industry->id,
            'organization_id' => $organization->id,
            'title' => 'Negadras Venture',
            'summary' => 'A polished submission summary.',
            'problem_statement' => 'The problem statement is now complete.',
            'solution_description' => 'The solution section is now complete.',
            'business_model' => 'The business model section is now complete.',
            'is_public_after_approval' => true,
        ])
        ->assertRedirect(route('submissions.show', $submission));

    expect($submission->fresh()->status)->toBe(SubmissionStatus::Submitted)
        ->and($submission->fresh()->submitted_at)->not()->toBeNull()
        ->and($submission->fresh()->organization_id)->toBe($organization->id);
});

test('presenter cannot view another presenters submission', function () {
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
    ]);

    $this->actingAs($intruder)
        ->get(route('submissions.show', $submission))
        ->assertForbidden();
});
