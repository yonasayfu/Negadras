<?php

namespace Database\Factories;

use App\AwardType;
use App\Models\AwardRecord;
use App\Models\RankingSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AwardRecord>
 */
class AwardRecordFactory extends Factory
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
            'season_id' => $rankingSnapshot->season_id,
            'submission_id' => $rankingSnapshot->submission_id,
            'ranking_snapshot_id' => $rankingSnapshot->id,
            'award_type' => fake()->randomElement(AwardType::cases()),
            'rank_position' => $rankingSnapshot->rank_position,
            'prize_value_optional' => fake()->randomFloat(2, 0, 50000),
            'notes' => fake()->sentence(),
            'granted_by' => null,
            'granted_at' => now(),
        ];
    }
}
