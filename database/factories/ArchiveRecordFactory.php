<?php

namespace Database\Factories;

use App\ArchiveStatus;
use App\Models\ArchiveRecord;
use App\Models\RankingSnapshot;
use App\PublicVisibilityStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArchiveRecord>
 */
class ArchiveRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rankingSnapshot = RankingSnapshot::factory()->create();

        return [
            'submission_id' => $rankingSnapshot->submission_id,
            'season_id' => $rankingSnapshot->season_id,
            'stage_id' => $rankingSnapshot->stage_id,
            'competition_session_id' => $rankingSnapshot->competition_session_id,
            'ranking_snapshot_id' => $rankingSnapshot->id,
            'archived_at' => now(),
            'archive_status' => ArchiveStatus::Archived,
            'public_visibility' => PublicVisibilityStatus::Private,
            'notes' => fake()->sentence(),
        ];
    }
}
