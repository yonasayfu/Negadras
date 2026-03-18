<?php

use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can view organizations index', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    Organization::factory()->count(2)->create();

    $this->actingAs($manager)
        ->get(route('organizations.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Organizations/Index')
            ->has('organizations.data', 2),
        );
});

test('manager can update organization details and replace logo', function () {
    Storage::fake('local');
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $industry = Industry::factory()->create(['is_active' => true]);
    $applicant = Applicant::factory()->create([
        'full_name' => 'Abel Founder',
        'email' => 'abel-founder@example.com',
    ]);

    $organization = Organization::factory()->create([
        'industry_id' => $industry->id,
        'display_name' => 'Old Name',
    ]);

    $organization->teamMembers()->create([
        'applicant_id' => $applicant->id,
        'full_name' => 'Abel Founder',
        'role_title' => 'Founder',
        'email' => 'abel-founder@example.com',
        'phone' => '0911554433',
        'bio' => 'Original founder.',
        'is_primary_contact' => true,
    ]);

    $this->actingAs($manager)
        ->put(route('organizations.update', $organization), [
            'legal_name' => 'Negadras Labs PLC',
            'display_name' => 'Negadras Labs',
            'registration_number' => 'NEG-BIZ-009',
            'industry_id' => $industry->id,
            'website' => 'https://labs.example.com',
            'description' => 'Updated by manager.',
            'contact_email' => 'contact@labs.example.com',
            'contact_phone' => '0911000000',
            'address' => 'Addis Ababa',
            'logo' => UploadedFile::fake()->image('negadras-logo.png'),
            'team_members' => [
                [
                    'id' => $organization->primaryContact?->id,
                    'full_name' => 'Abel Founder',
                    'role_title' => 'CEO',
                    'email' => 'abel-founder@example.com',
                    'phone' => '0911554433',
                    'bio' => 'Updated founder bio.',
                    'is_primary_contact' => true,
                ],
                [
                    'full_name' => 'Mimi Ops',
                    'role_title' => 'Operations',
                    'email' => 'mimi@example.com',
                    'phone' => '0911776655',
                    'bio' => 'Ops lead.',
                    'is_primary_contact' => false,
                ],
            ],
        ])
        ->assertRedirect(route('organizations.edit', $organization));

    expect($organization->fresh()->display_name)->toBe('Negadras Labs')
        ->and($organization->fresh()->teamMembers()->count())->toBe(2)
        ->and($organization->fresh()->primaryContact?->applicant_id)->toBe($applicant->id)
        ->and($organization->fresh()->logo)->not()->toBeNull();
});

test('member cannot access organization admin routes', function () {
    $this->seed(RolePermissionSeeder::class);

    $member = User::factory()->create();
    $member->assignRole('Member');
    $organization = Organization::factory()->create();

    $this->actingAs($member)
        ->get(route('organizations.index'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('organizations.edit', $organization))
        ->assertForbidden();
});
