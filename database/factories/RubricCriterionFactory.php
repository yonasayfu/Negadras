<?php

namespace Database\Factories;

use App\Models\Rubric;
use App\Models\RubricCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RubricCriterion>
 */
class RubricCriterionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'rubric_id' => Rubric::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'max_score' => 10,
            'weight' => 25,
            'order_index' => 1,
            'is_required' => true,
            'visibility_rule' => null,
            'help_text' => fake()->sentence(),
        ];
    }
}
