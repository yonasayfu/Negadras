<?php

use App\Models\RankingSnapshot;
use App\Models\User;
use App\OverrideEventType;
use Database\Seeders\RolePermissionSeeder;

test('manager ranking override creates a governance override event', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $snapshot = RankingSnapshot::factory()->create([
        'rank_position' => 4,
        'override_reason_optional' => null,
    ]);

    $this->actingAs($manager)
        ->put(route('rankings.update', $snapshot), [
            'rank_position' => 2,
            'override_reason_optional' => 'Board override after final moderation review',
        ])
        ->assertRedirect();

    $event = $snapshot->submission->overrideEvents()->latest('id')->firstOrFail();

    expect($event->event_type)->toBe(OverrideEventType::RankingOverride)
        ->and($event->reason)->toBe('Board override after final moderation review');
});
