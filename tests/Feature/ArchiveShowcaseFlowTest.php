<?php

use App\ArchiveStatus;
use App\AwardType;
use App\Models\CompetitionSession;
use App\Models\Panel;
use App\Models\RankingSnapshot;
use App\Models\Submission;
use App\Models\User;
use App\PublicVisibilityStatus;
use App\SubmissionStatus;
use Database\Seeders\RolePermissionSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('manager can archive a ranked submission and publish it to the public showcase', function () {
    $this->seed(RolePermissionSeeder::class);

    $manager = User::factory()->create();
    $manager->assignRole('Manager');

    $panel = Panel::factory()->create();

    $session = CompetitionSession::factory()->create([
        'season_id' => $panel->season_id,
        'stage_id' => $panel->stage_id,
        'panel_id' => $panel->id,
    ]);

    $submission = Submission::factory()->create([
        'season_id' => $panel->season_id,
        'current_stage_id' => $panel->stage_id,
        'status' => SubmissionStatus::Shortlisted,
        'title' => 'Public Showcase Candidate',
    ]);

    $snapshot = RankingSnapshot::factory()->create([
        'season_id' => $submission->season_id,
        'stage_id' => $submission->current_stage_id,
        'competition_session_id' => $session->id,
        'submission_id' => $submission->id,
        'aggregate_score' => 92.4,
        'rank_position' => 1,
        'finalized_at' => now(),
    ]);

    $this->actingAs($manager)
        ->post(route('archive.store'), [
            'submission_id' => $submission->id,
            'ranking_snapshot_id' => $snapshot->id,
            'competition_session_id' => $session->id,
            'archive_status' => ArchiveStatus::Published->value,
            'public_visibility' => PublicVisibilityStatus::Public->value,
            'notes' => 'Published after final awards ceremony.',
            'showcase_title' => 'Public Showcase Candidate',
            'showcase_subtitle' => 'Winner of the final season session',
            'showcase_summary' => 'A public archive summary for the final winners showcase.',
            'showcase_winner_label' => AwardType::Winner->label(),
        ])
        ->assertRedirect();

    $archiveRecord = $submission->archiveRecords()->first();

    expect($archiveRecord)->not->toBeNull()
        ->and($archiveRecord?->public_visibility)->toBe(PublicVisibilityStatus::Public);

    $this->actingAs($manager)
        ->post(route('archive.highlights.store'), [
            'competition_session_id' => $session->id,
            'title' => 'Top live moment',
            'summary' => 'The strongest closing pitch of the evening.',
            'quote_optional' => 'We built this for the operators who actually run the economy.',
            'quote_source_optional' => 'Lead presenter',
            'display_order' => 1,
            'is_public' => true,
        ])
        ->assertRedirect();

    $entry = $archiveRecord?->showcaseEntry()->first();

    expect($entry)->not->toBeNull();

    $this->get(route('showcase.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Showcase/Index')
            ->has('entries', 1)
            ->where('entries.0.slug', $entry?->slug),
        );

    $this->get(route('showcase.show', $entry?->slug))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/Showcase/Show')
            ->where('entry.title', $entry?->title)
            ->where('entry.highlights.0.title', 'Top live moment'),
        );
});
