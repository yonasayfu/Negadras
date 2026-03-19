<?php

namespace Database\Factories;

use App\Models\ShortlistRecord;
use App\Models\Submission;
use App\Models\User;
use App\ShortlistApprovalStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShortlistRecord>
 */
class ShortlistRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $submission = Submission::factory()->create();

        return [
            'submission_id' => $submission->id,
            'stage_id' => $submission->current_stage_id,
            'rank_order_optional' => fake()->optional()->numberBetween(1, 10),
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
            'approval_status' => ShortlistApprovalStatus::Pending,
        ];
    }
}
