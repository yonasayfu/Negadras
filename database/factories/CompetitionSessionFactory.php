<?php

namespace Database\Factories;

use App\CompetitionSessionStatus;
use App\CompetitionSessionType;
use App\Models\CompetitionSession;
use App\Models\Panel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionSession>
 */
class CompetitionSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $panel = Panel::factory()->create();

        return [
            'season_id' => $panel->season_id,
            'stage_id' => $panel->stage_id,
            'panel_id' => $panel->id,
            'name' => fake()->words(3, true),
            'session_type' => CompetitionSessionType::Pitch,
            'scheduled_at' => now()->addDay(),
            'broadcasted_at' => null,
            'location' => fake()->city(),
            'status' => CompetitionSessionStatus::Scheduled,
            'etv_video_url_optional' => null,
            'started_at' => null,
            'paused_at' => null,
            'completed_at' => null,
            'scores_revealed' => false,
        ];
    }
}
