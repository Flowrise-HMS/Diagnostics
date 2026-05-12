<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;

class DiagnosticObservationFactory extends Factory
{
    protected $model = DiagnosticObservation::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'specimen_id' => null,
            'code' => fake()->bothify('OBS-###??'),
            'status' => 'registered',
        ];
    }
}
