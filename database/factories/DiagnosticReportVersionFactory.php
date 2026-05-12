<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticReportVersion;

class DiagnosticReportVersionFactory extends Factory
{
    protected $model = DiagnosticReportVersion::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'version' => 1,
            'status' => 'preliminary',
        ];
    }
}
