<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticReferenceRangeFactory extends Factory
{
    protected $model = DiagnosticReferenceRange::class;

    public function definition(): array
    {
        $min = fake()->randomFloat(2, 1, 50);
        $max = $min + fake()->randomFloat(2, 10, 100);

        return [
            'profile_id' => DiagnosticServiceProfile::factory(),
            'gender' => fake()->randomElement(['any', 'male', 'female', 'other']),
            'age_min_months' => fake()->optional()->numberBetween(0, 120),
            'age_max_months' => fake()->optional()->numberBetween(121, 1200),
            'min_value' => $min,
            'max_value' => $max,
            'range_text' => null,
            'units' => fake()->randomElement(['g/dL', 'mg/dL', 'mmol/L', '%']),
            'critical_low' => $min - fake()->randomFloat(2, 1, 5),
            'critical_high' => $max + fake()->randomFloat(2, 1, 5),
        ];
    }
}
