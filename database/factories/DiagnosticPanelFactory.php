<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticPanel;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticPanelFactory extends Factory
{
    protected $model = DiagnosticPanel::class;

    public function definition(): array
    {
        return [
            'profile_id' => DiagnosticServiceProfile::factory(),
        ];
    }
}
