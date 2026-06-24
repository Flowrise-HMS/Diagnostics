<?php

namespace Modules\Diagnostics\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticSpecimen;
use Modules\Diagnostics\Models\DiagnosticSpecimenProcessingEvent;

class DiagnosticSpecimenProcessingEventFactory extends Factory
{
    protected $model = DiagnosticSpecimenProcessingEvent::class;

    public function definition(): array
    {
        return [
            'specimen_id' => DiagnosticSpecimen::factory(),
            'procedure' => fake()->randomElement(['centrifugation', 'aliquoting', 'freezing']),
            'additive' => fake()->optional()->word(),
            'processed_by' => User::factory(),
            'processed_at' => fake()->dateTimeBetween('-2 days', 'now'),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
