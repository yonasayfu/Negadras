<?php

namespace Database\Factories;

use App\Models\Panel;
use App\Models\Rubric;
use App\Models\Season;
use App\Models\Stage;
use App\PanelStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Panel>
 */
class PanelFactory extends Factory
{
    public function definition(): array
    {
        $season = Season::factory()->create();
        $stage = Stage::factory()->create([
            'season_id' => $season->id,
        ]);

        return [
            'season_id' => $season->id,
            'stage_id' => $stage->id,
            'rubric_id' => Rubric::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'status' => PanelStatus::Active,
        ];
    }
}
