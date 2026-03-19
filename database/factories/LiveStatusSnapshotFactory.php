<?php

namespace Database\Factories;

use App\Models\CompetitionSession;
use App\Models\LiveStatusSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveStatusSnapshot>
 */
class LiveStatusSnapshotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_session_id' => CompetitionSession::factory(),
            'current_session_presenter_id' => null,
            'status_payload' => ['session' => ['name' => fake()->words(2, true)]],
            'updated_by' => User::factory(),
        ];
    }
}
