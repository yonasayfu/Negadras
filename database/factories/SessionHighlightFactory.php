<?php

namespace Database\Factories;

use App\Models\CompetitionSession;
use App\Models\SessionHighlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionHighlight>
 */
class SessionHighlightFactory extends Factory
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
            'title' => fake()->sentence(4),
            'summary' => fake()->paragraph(),
            'quote_optional' => fake()->boolean() ? fake()->sentence() : null,
            'quote_source_optional' => fake()->boolean() ? fake()->name() : null,
            'display_order' => fake()->numberBetween(1, 5),
            'is_public' => fake()->boolean(),
        ];
    }
}
