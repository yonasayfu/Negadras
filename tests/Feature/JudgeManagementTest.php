<?php

use App\Models\Judge;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can create a judge profile and assign the judge role', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $judgeUser = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('judges.store'), [
            'user_id' => $judgeUser->id,
            'professional_title' => 'Investment committee lead',
            'organization' => 'Negadras Capital',
            'specialization' => 'Venture finance',
            'bio' => 'Experienced capital deployment leader.',
            'is_active' => true,
        ])
        ->assertRedirect();

    $judge = Judge::query()->firstOrFail();

    expect($judge->user_id)->toBe($judgeUser->id)
        ->and($judgeUser->fresh()->hasRole('Judge'))->toBeTrue();
});

test('manager can inspect the judge management page', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    Judge::factory()->create();

    $this->actingAs($manager)
        ->get(route('judges.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Judges/Index')
            ->has('judges.data', 1),
        );
});

test('member cannot access judge management', function () {
    $this->seed(RolePermissionSeeder::class);

    $member = User::factory()->create();
    $member->assignRole('Member');

    $this->actingAs($member)
        ->get(route('judges.index'))
        ->assertForbidden();
});
