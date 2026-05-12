<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticStudy;

class DiagnosticStudyFactory extends Factory
{
    protected $model = DiagnosticStudy::class;

    public function definition(): array
    {
        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'status' => 'registered',
        ];
    }
}
