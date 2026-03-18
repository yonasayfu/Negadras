<?php

use App\Models\Industry;
use App\Models\Season;
use App\Models\Stage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('admin can view competition structure indexes', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $season = Season::factory()->create();
    Season::factory()->create();
    Stage::factory()->count(2)->create([
        'season_id' => $season->id,
    ]);
    Industry::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('seasons.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Seasons/Index')
            ->has('seasons.data', 2),
        );

    $this->actingAs($admin)
        ->get(route('stages.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Stages/Index')
            ->has('stages.data', 2),
        );

    $this->actingAs($admin)
        ->get(route('industries.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Industries/Index')
            ->has('industries.data', 2),
        );
});

test('manager can create and update season stage and industry records', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $this->actingAs($manager)
        ->post(route('seasons.store'), [
            'name' => 'Negadras 2026',
            'year' => 2026,
            'slug' => 'negadras-2026',
            'status' => 'draft',
            'registration_open_at' => now()->addDay()->toDateTimeString(),
            'registration_close_at' => now()->addDays(10)->toDateTimeString(),
            'description' => 'Main competition season.',
        ])
        ->assertRedirect();

    $season = Season::query()->where('slug', 'negadras-2026')->firstOrFail();

    $this->actingAs($manager)
        ->post(route('stages.store'), [
            'season_id' => $season->id,
            'name' => 'Intake',
            'code' => 'intake',
            'type' => 'intake',
            'order_index' => 1,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDays(5)->toDateTimeString(),
            'status' => 'draft',
            'is_live_stage' => false,
        ])
        ->assertRedirect();

    $stage = Stage::query()->where('code', 'intake')->firstOrFail();

    $this->actingAs($manager)
        ->post(route('industries.store'), [
            'name' => 'Agritech',
            'slug' => 'agritech',
            'description' => 'Agriculture innovation.',
            'is_active' => true,
        ])
        ->assertRedirect();

    $industry = Industry::query()->where('slug', 'agritech')->firstOrFail();

    $this->actingAs($manager)
        ->put(route('seasons.update', $season), [
            'name' => 'Negadras 2026 Prime',
            'year' => 2026,
            'slug' => 'negadras-2026-prime',
            'status' => 'active',
            'registration_open_at' => now()->addDay()->toDateTimeString(),
            'registration_close_at' => now()->addDays(12)->toDateTimeString(),
            'description' => 'Updated season.',
        ])
        ->assertRedirect(route('seasons.edit', $season));

    $this->actingAs($manager)
        ->put(route('stages.update', $stage), [
            'season_id' => $season->id,
            'name' => 'Screening',
            'code' => 'screening',
            'type' => 'screening',
            'order_index' => 2,
            'starts_at' => now()->addDays(2)->toDateTimeString(),
            'ends_at' => now()->addDays(6)->toDateTimeString(),
            'status' => 'open',
            'is_live_stage' => false,
        ])
        ->assertRedirect(route('stages.edit', $stage));

    $this->actingAs($manager)
        ->put(route('industries.update', $industry), [
            'name' => 'HealthTech',
            'slug' => 'healthtech',
            'description' => 'Health innovation.',
            'is_active' => true,
        ])
        ->assertRedirect(route('industries.edit', $industry));

    expect($season->fresh()->slug)->toBe('negadras-2026-prime')
        ->and($season->fresh()->status->value)->toBe('active')
        ->and($stage->fresh()->code)->toBe('screening')
        ->and($stage->fresh()->status->value)->toBe('open')
        ->and($industry->fresh()->slug)->toBe('healthtech');
});

test('member cannot access competition structure routes', function () {
    $this->seed(RolePermissionSeeder::class);

    $member = User::factory()->create();
    $member->assignRole('Member');
    $season = Season::factory()->create();

    $this->actingAs($member)->get(route('seasons.index'))->assertForbidden();
    $this->actingAs($member)->get(route('stages.index'))->assertForbidden();
    $this->actingAs($member)->get(route('industries.index'))->assertForbidden();
    $this->actingAs($member)->get(route('seasons.edit', $season))->assertForbidden();
});

test('duplicate stage code in same season is rejected', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');
    $season = Season::factory()->create();

    Stage::factory()->create([
        'season_id' => $season->id,
        'code' => 'screening',
    ]);

    $this->actingAs($manager)
        ->post(route('stages.store'), [
            'season_id' => $season->id,
            'name' => 'Second screening',
            'code' => 'screening',
            'type' => 'screening',
            'order_index' => 2,
            'starts_at' => now()->addDay()->toDateTimeString(),
            'ends_at' => now()->addDays(2)->toDateTimeString(),
            'status' => 'draft',
            'is_live_stage' => false,
        ])
        ->assertSessionHasErrors('code');
});

test('admin can activate and close seasons open and close stages and toggle industries', function () {
    $this->seed(RolePermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $season = Season::factory()->create(['status' => 'draft']);
    $stage = Stage::factory()->create(['status' => 'draft']);
    $industry = Industry::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->post(route('seasons.activate', $season))
        ->assertRedirect(route('seasons.index'));

    $this->actingAs($admin)
        ->post(route('seasons.close', $season))
        ->assertRedirect(route('seasons.index'));

    $this->actingAs($admin)
        ->post(route('stages.open', $stage))
        ->assertRedirect(route('stages.index'));

    $this->actingAs($admin)
        ->post(route('stages.close', $stage))
        ->assertRedirect(route('stages.index'));

    $this->actingAs($admin)
        ->post(route('industries.toggle', $industry))
        ->assertRedirect(route('industries.index'));

    expect($season->fresh()->status->value)->toBe('closed')
        ->and($stage->fresh()->status->value)->toBe('closed')
        ->and($industry->fresh()->is_active)->toBeFalse();
});
