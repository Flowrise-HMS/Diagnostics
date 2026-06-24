<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Enums\ReportVersionStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticReportVersion;

class DiagnosticReportVersionFactory extends Factory
{
    protected $model = DiagnosticReportVersion::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'report_number' => fake()->optional()->bothify('RPT-########'),
            'version' => 1,
            'title' => fake()->optional()->sentence(3),
            'status' => ReportVersionStatus::PRELIMINARY,
            'conclusion' => fake()->optional()->paragraph(),
            'is_critical' => false,
            'metadata' => null,
        ];
    }
}
