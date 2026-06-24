<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\ObservationStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;

class DiagnosticObservationFactory extends Factory
{
    protected $model = DiagnosticObservation::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'profile_id' => null,
            'parent_observation_id' => null,
            'specimen_id' => null,
            'code' => fake()->bothify('OBS-###??'),
            'display' => fake()->optional()->words(3, true),
            'status' => ObservationStatus::REGISTERED,
            'value_type' => 'numeric',
            'value_numeric' => fake()->optional()->randomFloat(2, 1, 200),
            'units' => fake()->optional()->randomElement(['g/dL', 'mg/dL', '%']),
            'abnormal_flag' => AbnormalFlag::NORMAL,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
