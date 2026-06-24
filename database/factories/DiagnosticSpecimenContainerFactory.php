<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticSpecimen;
use Modules\Diagnostics\Models\DiagnosticSpecimenContainer;

class DiagnosticSpecimenContainerFactory extends Factory
{
    protected $model = DiagnosticSpecimenContainer::class;

    public function definition(): array
    {
        return [
            'specimen_id' => DiagnosticSpecimen::factory(),
            'container_type' => fake()->randomElement(['vacutainer', 'urine_cup', 'swab_tube']),
            'additive' => fake()->optional()->randomElement(['EDTA', 'heparin', 'none']),
            'capacity' => fake()->optional()->randomFloat(2, 2, 10),
            'capacity_unit' => 'mL',
            'identifier' => fake()->optional()->bothify('CNT-########'),
        ];
    }
}
