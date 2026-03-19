<?php

namespace Database\Factories;

use App\ExportJobStatus;
use App\Models\ExportJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportJob>
 */
class ExportJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement([
                'submissions_csv',
                'shortlist_csv',
                'rankings_csv',
                'awards_csv',
                'archive_csv',
            ]),
            'filters' => [],
            'requested_by' => User::factory(),
            'status' => ExportJobStatus::Completed,
            'file_name' => fake()->slug().'.csv',
            'row_count' => fake()->numberBetween(1, 80),
            'completed_at' => now(),
        ];
    }
}
