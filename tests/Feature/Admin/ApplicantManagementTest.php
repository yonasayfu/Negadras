<?php

use App\Models\Applicant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can view applicants index', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    Applicant::factory()->count(2)->create();

    $this->actingAs($manager)
        ->get(route('applicants.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Applicants/Index')
            ->has('applicants.data', 2),
        );
});

test('manager can update applicant details and verify social links', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $applicant = Applicant::factory()->create();

    $this->actingAs($manager)
        ->put(route('applicants.update', $applicant), [
            'applicant_type' => 'organization',
            'full_name' => 'Negadras Ventures',
            'email' => 'ventures@example.com',
            'phone' => '0911000000',
            'bio' => 'Updated by manager.',
            'national_id_or_registration_ref' => 'REG-0091',
            'social_links' => [
                [
                    'platform' => 'Website',
                    'url' => 'https://ventures.example.com',
                    'is_verified' => true,
                ],
            ],
        ])
        ->assertRedirect(route('applicants.edit', $applicant));

    expect($applicant->fresh()->applicant_type->value)->toBe('organization')
        ->and($applicant->fresh()->full_name)->toBe('Negadras Ventures')
        ->and($applicant->fresh()->socialLinks()->count())->toBe(1)
        ->and($applicant->fresh()->socialLinks()->first()?->is_verified)->toBeTrue();
});

test('member cannot access applicant admin routes', function () {
    $this->seed(RolePermissionSeeder::class);

    $member = User::factory()->create();
    $member->assignRole('Member');
    $applicant = Applicant::factory()->create();

    $this->actingAs($member)
        ->get(route('applicants.index'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('applicants.edit', $applicant))
        ->assertForbidden();
});
