<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticSpecimen;

class DiagnosticSpecimenFactory extends Factory
{
    protected $model = DiagnosticSpecimen::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'specimen_type' => fake()->randomElement(['blood', 'urine', 'swab', 'tissue']),
            'status' => 'collected',
        ];
    }
}
