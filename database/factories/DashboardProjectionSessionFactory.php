<?php

namespace Database\Factories;

use App\DashboardProjectionStatus;
use App\Models\CompetitionSession;
use App\Models\DashboardProjectionSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardProjectionSession>
 */
class DashboardProjectionSessionFactory extends Factory
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
            'status' => DashboardProjectionStatus::Requested,
            'source_label' => fake()->word(),
            'requested_by' => User::factory(),
            'approved_by' => null,
            'requested_at' => now(),
            'approved_at' => null,
            'started_at' => null,
            'ended_at' => null,
            'notes' => fake()->sentence(),
        ];
    }
}
