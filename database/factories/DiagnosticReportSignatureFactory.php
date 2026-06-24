<?php

namespace Modules\Diagnostics\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticReportSignature;
use Modules\Diagnostics\Models\DiagnosticReportVersion;

class DiagnosticReportSignatureFactory extends Factory
{
    protected $model = DiagnosticReportSignature::class;

    public function definition(): array
    {
        return [
            'report_version_id' => DiagnosticReportVersion::factory(),
            'signed_by' => User::factory(),
            'signature_type' => fake()->randomElement(['performed', 'verified', 'signed', 'countersigned']),
            'signature' => fake()->optional()->uuid(),
            'role' => fake()->randomElement(['pathologist', 'radiologist', 'laboratory_scientist']),
            'signed_at' => fake()->dateTimeBetween('-2 days', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
