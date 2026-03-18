<?php

use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('signed in user with applicant profile can view organization profile settings page', function () {
    $user = User::factory()->create();
    $applicant = Applicant::factory()->for($user)->create();
    $industry = Industry::factory()->create(['is_active' => true]);

    $organization = Organization::factory()->create([
        'industry_id' => $industry->id,
        'display_name' => 'Blue Nile Labs',
    ]);

    $organization->teamMembers()->create([
        'applicant_id' => $applicant->id,
        'full_name' => $applicant->full_name,
        'role_title' => 'Primary contact',
        'email' => $applicant->email,
        'phone' => $applicant->phone,
        'bio' => $applicant->bio,
        'is_primary_contact' => true,
    ]);

    $this->actingAs($user)
        ->get(route('organization-profile.edit'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/OrganizationProfile')
            ->where('organization.displayName', 'Blue Nile Labs'),
        );
});

test('signed in user can create own organization profile with logo and team members', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $applicant = Applicant::factory()->for($user)->create([
        'full_name' => 'Alemu Desta',
        'email' => 'alemu@example.com',
        'phone' => '0911223344',
    ]);
    $industry = Industry::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->put(route('organization-profile.update'), [
            'legal_name' => 'Alemu Ventures PLC',
            'display_name' => 'Alemu Ventures',
            'registration_number' => 'NEG-BIZ-001',
            'industry_id' => $industry->id,
            'website' => 'https://alemu.example.com',
            'description' => 'Startup profile for the Negadras competition.',
            'contact_email' => 'team@alemu.example.com',
            'contact_phone' => '0911998877',
            'address' => 'Addis Ababa',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'team_members' => [
                [
                    'full_name' => 'Alemu Desta',
                    'role_title' => 'Primary contact',
                    'email' => 'alemu@example.com',
                    'phone' => '0911223344',
                    'bio' => 'Founder and presenter.',
                    'is_primary_contact' => true,
                ],
                [
                    'full_name' => 'Sara Bekele',
                    'role_title' => 'Operations',
                    'email' => 'sara@example.com',
                    'phone' => '0911887766',
                    'bio' => 'Operations lead.',
                    'is_primary_contact' => false,
                ],
            ],
        ])
        ->assertRedirect(route('organization-profile.edit'));

    $organization = Organization::query()->firstOrFail();

    expect($organization->display_name)->toBe('Alemu Ventures')
        ->and($organization->teamMembers()->count())->toBe(2)
        ->and($organization->primaryContact?->applicant_id)->toBe($applicant->id)
        ->and($organization->logo)->not()->toBeNull();

    Storage::disk('local')->assertExists($organization->logo_path);
});

test('organization profile requires exactly one primary contact', function () {
    $user = User::factory()->create();
    Applicant::factory()->for($user)->create([
        'email' => 'alemu@example.com',
    ]);

    $this->actingAs($user)
        ->from(route('organization-profile.edit'))
        ->put(route('organization-profile.update'), [
            'legal_name' => 'Alemu Ventures PLC',
            'display_name' => 'Alemu Ventures',
            'registration_number' => 'NEG-BIZ-002',
            'team_members' => [
                [
                    'full_name' => 'Alemu Desta',
                    'role_title' => 'Presenter',
                    'email' => 'alemu@example.com',
                    'phone' => '0911223344',
                    'bio' => 'Founder.',
                    'is_primary_contact' => false,
                ],
                [
                    'full_name' => 'Sara Bekele',
                    'role_title' => 'Operations',
                    'email' => 'sara@example.com',
                    'phone' => '0911887766',
                    'bio' => 'Operations.',
                    'is_primary_contact' => false,
                ],
            ],
        ])
        ->assertRedirect(route('organization-profile.edit'))
        ->assertSessionHasErrors('team_members');
});
