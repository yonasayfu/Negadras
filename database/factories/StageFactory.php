<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\Stage;
use App\StageStatus;
use App\StageType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stage>
 */
class StageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'season_id' => Season::factory(),
            'name' => fake()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('STG??')),
            'type' => fake()->randomElement(StageType::cases()),
            'order_index' => fake()->numberBetween(1, 8),
            'starts_at' => now()->addDays(fake()->numberBetween(1, 10)),
            'ends_at' => now()->addDays(fake()->numberBetween(11, 20)),
            'status' => fake()->randomElement(StageStatus::cases()),
            'is_live_stage' => fake()->boolean(20),
        ];
    }
}
