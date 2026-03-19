<?php

namespace Database\Factories;

use App\Models\Applicant;
use App\Models\Industry;
use App\Models\Season;
use App\Models\Stage;
use App\Models\Submission;
use App\SubmissionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
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
            'current_stage_id' => null,
            'industry_id' => Industry::factory(),
            'applicant_id' => Applicant::factory(),
            'organization_id' => null,
            'title' => fake()->sentence(4),
            'summary' => fake()->paragraph(),
            'problem_statement' => fake()->paragraph(),
            'solution_description' => fake()->paragraph(),
            'business_model' => fake()->paragraph(),
            'status' => SubmissionStatus::Draft,
            'submitted_at' => null,
            'is_public_after_approval' => false,
            'current_version_id' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Submission $submission): void {
            if ($submission->current_stage_id !== null) {
                return;
            }

            $stage = Stage::factory()->create([
                'season_id' => $submission->season_id,
            ]);

            $submission->forceFill([
                'current_stage_id' => $stage->id,
            ])->save();
        });
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => [
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }
}
