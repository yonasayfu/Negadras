<?php

use App\Models\CompetitionSession;
use App\Models\Panel;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can create a competition session', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $panel = Panel::factory()->create();

    $this->actingAs($manager)
        ->post(route('competition-sessions.store'), [
            'season_id' => $panel->season_id,
            'stage_id' => $panel->stage_id,
            'panel_id' => $panel->id,
            'name' => 'Final pitch block A',
            'session_type' => 'pitch',
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'location' => 'Main hall',
            'status' => 'scheduled',
            'etv_video_url_optional' => null,
            'media_ids' => [],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('competition_sessions', [
        'name' => 'Final pitch block A',
        'panel_id' => $panel->id,
    ]);
});

test('manager can inspect the competition sessions index', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    CompetitionSession::factory()->create();

    $this->actingAs($manager)
        ->get(route('competition-sessions.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/CompetitionSessions/Index')
            ->has('sessions.data', 1),
        );
});
