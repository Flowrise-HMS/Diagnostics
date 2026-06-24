<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Enums\StudyStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticStudy;

class DiagnosticStudyFactory extends Factory
{
    protected $model = DiagnosticStudy::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'uid' => fake()->optional()->uuid(),
            'accession_number' => fake()->optional()->bothify('IMG-########'),
            'modality' => fake()->optional()->randomElement(['CT', 'MR', 'US', 'XR']),
            'body_site' => fake()->optional()->word(),
            'number_of_series' => fake()->numberBetween(0, 5),
            'status' => StudyStatus::REGISTERED,
            'metadata' => null,
        ];
    }
}
