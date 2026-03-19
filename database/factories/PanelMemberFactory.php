<?php

namespace Database\Factories;

use App\Models\Judge;
use App\Models\Panel;
use App\Models\PanelMember;
use App\PanelRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanelMember>
 */
class PanelMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'panel_id' => Panel::factory(),
            'judge_id' => Judge::factory(),
            'role_in_panel' => PanelRole::Member,
            'display_order' => 1,
        ];
    }
}
