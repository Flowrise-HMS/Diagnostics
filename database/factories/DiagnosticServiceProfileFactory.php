<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticServiceProfileFactory extends Factory
{
    protected $model = DiagnosticServiceProfile::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'discipline' => fake()->randomElement(['lab', 'radiology', 'pathology']),
            'loinc_code' => fake()->optional()->numerify('#####-#'),
            'loinc_display' => fake()->optional()->sentence(3),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
