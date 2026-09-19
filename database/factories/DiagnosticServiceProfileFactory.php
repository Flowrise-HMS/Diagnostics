<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Enums\ServiceCategoryCode;
use Modules\Core\Models\Service;
use Modules\Core\Models\ServiceCategory;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticServiceProfileFactory extends Factory
{
    protected $model = DiagnosticServiceProfile::class;

    public function definition(): array
    {
        return [
            // A non-diagnostic category, so the auto-setup observer never races this factory
            // for the unique service_id.
            'service_id' => Service::factory()->forCategory(
                ServiceCategory::query()->firstOrCreate(
                    ['code' => ServiceCategoryCode::CON->value],
                    ['name' => ServiceCategoryCode::CON->getLabel(), 'is_active' => true],
                ),
            ),
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
