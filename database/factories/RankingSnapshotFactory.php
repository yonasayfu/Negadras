<?php

namespace Database\Factories;

use App\Models\RankingSnapshot;
use App\Models\Stage;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RankingSnapshot>
 */
class RankingSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submission = Submission::factory()->create();
        $stage = Stage::factory()->create([
            'season_id' => $submission->season_id,
        ]);

        return [
            'season_id' => $submission->season_id,
            'stage_id' => $stage->id,
            'competition_session_id' => null,
            'submission_id' => $submission->id,
            'aggregate_score' => fake()->randomFloat(2, 60, 100),
            'rank_position' => fake()->numberBetween(1, 10),
            'tie_break_reason_optional' => null,
            'override_reason_optional' => null,
            'overridden_by' => null,
            'finalized_at' => now(),
        ];
    }
}
