<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;

class DiagnosticResultTemplateFieldFactory extends Factory
{
    protected $model = DiagnosticResultTemplateField::class;

    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'template_id' => DiagnosticResultTemplate::factory(),
            'observation_code' => fake()->optional()->numerify('#####-#'),
            'observation_name' => str($label)->title()->toString(),
            'data_type' => fake()->randomElement(['numeric', 'text', 'coded', 'boolean', 'range']),
            'field_key' => str($label)->snake()->toString(),
            'label' => str($label)->title()->toString(),
            'value_type' => fake()->randomElement(['numeric', 'text', 'select']),
            'default_units' => fake()->optional()->randomElement(['g/dL', 'mg/dL', '%']),
            'is_required' => fake()->boolean(30),
            'reference_range_low' => fake()->optional()->randomFloat(2, 1, 50),
            'reference_range_high' => fake()->optional()->randomFloat(2, 51, 200),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
