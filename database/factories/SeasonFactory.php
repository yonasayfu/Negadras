<?php

namespace Database\Factories;

use App\Models\Season;
use App\SeasonStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = sprintf('Negadras %s', fake()->year());

        return [
            'name' => $name,
            'year' => (int) fake()->year(),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999),
            'status' => fake()->randomElement(SeasonStatus::cases()),
            'registration_open_at' => now()->subWeeks(2),
            'registration_close_at' => now()->addWeeks(4),
            'description' => fake()->sentence(),
        ];
    }
}
