<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticObservationComponent;

class DiagnosticObservationComponentFactory extends Factory
{
    protected $model = DiagnosticObservationComponent::class;

    public function definition(): array
    {
        return [
            'observation_id' => DiagnosticObservation::factory(),
            'code' => fake()->bothify('COMP-###??'),
            'display' => fake()->words(2, true),
            'value_type' => 'numeric',
            'value_numeric' => fake()->randomFloat(2, 1, 200),
            'units' => fake()->randomElement(['mmHg', 'g/dL', 'mg/dL']),
            'abnormal_flag' => AbnormalFlag::NORMAL,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
