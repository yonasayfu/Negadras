<?php

namespace Database\Factories;

use App\Models\CompetitionSession;
use App\Models\Media;
use App\Models\SessionMedium;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionMedium>
 */
class SessionMediumFactory extends Factory
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
            'media_id' => Media::factory(),
            'usage_type' => 'supporting',
            'display_order' => 1,
        ];
    }
}
