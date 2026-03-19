<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionFile>
 */
class SubmissionFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => Submission::factory(),
            'submission_version_id' => null,
            'file_type' => 'application_pdf',
            'disk' => 'local',
            'original_name' => 'application.pdf',
            'file_path' => 'negadras/submissions/example/application.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'description' => fake()->sentence(),
            'uploaded_by' => User::factory(),
            'uploaded_at' => now(),
            'is_required' => true,
            'is_verified' => false,
        ];
    }
}
