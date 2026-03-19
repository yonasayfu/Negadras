<?php

namespace Database\Factories;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_user_id' => User::factory(),
            'sent_by' => User::factory(),
            'category' => fake()->randomElement(['review', 'feedback', 'governance']),
            'channel' => 'database',
            'title' => fake()->sentence(3),
            'message' => fake()->sentence(),
            'action_url' => '/dashboard',
            'action_label' => 'Open',
            'level' => fake()->randomElement(['info', 'success', 'warning']),
            'sent_at' => now(),
        ];
    }
}
