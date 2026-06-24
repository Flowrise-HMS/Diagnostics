<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticPanel;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticPanelItemFactory extends Factory
{
    protected $model = DiagnosticPanelItem::class;

    public function definition(): array
    {
        return [
            'panel_id' => DiagnosticPanel::factory(),
            'child_profile_id' => DiagnosticServiceProfile::factory(),
            'sequence' => fake()->numberBetween(1, 10),
            'is_required' => true,
        ];
    }
}
