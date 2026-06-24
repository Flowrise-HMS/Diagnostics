<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Enums\SpecimenCondition;
use Modules\Diagnostics\Enums\SpecimenStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticSpecimen;

class DiagnosticSpecimenFactory extends Factory
{
    protected $model = DiagnosticSpecimen::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'accession_number' => fake()->optional()->bothify('SPC-########'),
            'specimen_type' => fake()->randomElement(['blood', 'urine', 'swab', 'tissue']),
            'specimen_class' => fake()->optional()->randomElement(['fluid', 'tissue', 'cell']),
            'barcode' => fake()->optional()->bothify('BC-##########'),
            'collected_at' => fake()->optional()->dateTimeBetween('-2 days', 'now'),
            'condition' => SpecimenCondition::ACCEPTABLE,
            'status' => SpecimenStatus::COLLECTED,
        ];
    }
}
