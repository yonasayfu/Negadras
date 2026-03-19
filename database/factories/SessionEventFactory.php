<?php

namespace Database\Factories;

use App\Models\CompetitionSession;
use App\Models\SessionEvent;
use App\Models\User;
use App\SessionEventType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionEvent>
 */
class SessionEventFactory extends Factory
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
            'event_type' => SessionEventType::SessionStarted,
            'payload_json' => [],
            'created_by' => User::factory(),
            'created_at' => now(),
        ];
    }
}
