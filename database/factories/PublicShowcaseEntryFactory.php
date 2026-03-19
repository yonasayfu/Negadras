<?php

namespace Database\Factories;

use App\Models\ArchiveRecord;
use App\Models\PublicShowcaseEntry;
use App\PublicVisibilityStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PublicShowcaseEntry>
 */
class PublicShowcaseEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $archiveRecord = ArchiveRecord::factory()->create();

        return [
            'archive_record_id' => $archiveRecord->id,
            'season_id' => $archiveRecord->season_id,
            'industry_id' => $archiveRecord->submission?->industry_id,
            'media_id_optional' => null,
            'slug' => Str::slug(fake()->unique()->words(3, true)),
            'title' => $archiveRecord->submission?->title ?? fake()->sentence(3),
            'subtitle' => fake()->sentence(),
            'visibility_status' => PublicVisibilityStatus::Public,
            'summary' => fake()->paragraph(),
            'winner_label' => fake()->boolean() ? 'Finalist' : null,
        ];
    }
}
