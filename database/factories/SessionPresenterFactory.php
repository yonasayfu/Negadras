<?php

namespace Database\Factories;

use App\Models\CompetitionSession;
use App\Models\SessionPresenter;
use App\Models\Submission;
use App\SessionAppearanceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionPresenter>
 */
class SessionPresenterFactory extends Factory
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
            'submission_id' => Submission::factory(),
            'order_index' => 1,
            'appearance_status' => SessionAppearanceStatus::Queued,
            'started_at' => null,
            'ended_at' => null,
        ];
    }
}
