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
            'field_key' => str($label)->snake()->toString(),
            'label' => str($label)->title()->toString(),
            'value_type' => fake()->randomElement(['numeric', 'text', 'select']),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
