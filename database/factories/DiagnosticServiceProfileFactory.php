<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticServiceProfileFactory extends Factory
{
    protected $model = DiagnosticServiceProfile::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'discipline' => fake()->randomElement(DiagnosticDiscipline::cases()),
            'loinc_code' => fake()->optional()->numerify('#####-#'),
            'loinc_display' => fake()->optional()->sentence(3),
            'default_specimen_type' => fake()->optional()->randomElement(['blood', 'urine', 'serum']),
            'preparation_instructions' => fake()->optional()->sentence(),
            'auto_verify_eligible' => false,
            'turnaround_time_minutes' => fake()->optional()->numberBetween(30, 480),
            'modality' => fake()->optional()->randomElement(['CT', 'MR', 'US', 'XR']),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
