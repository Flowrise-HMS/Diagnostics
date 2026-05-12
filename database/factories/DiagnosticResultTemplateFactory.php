<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticResultTemplateFactory extends Factory
{
    protected $model = DiagnosticResultTemplate::class;

    public function definition(): array
    {
        return [
            'profile_id' => DiagnosticServiceProfile::factory(),
            'name' => fake()->words(3, true),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
