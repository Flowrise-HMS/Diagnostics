<?php

namespace Modules\Diagnostics\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticResultFile;

class DiagnosticResultFileFactory extends Factory
{
    protected $model = DiagnosticResultFile::class;

    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'jpg', 'png']);

        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'report_version_id' => null,
            'file_type' => $extension,
            'source' => fake()->randomElement(['internal_entry', 'external_lab', 'external_facility']),
            'file_name' => fake()->lexify('diagnostic-report-????').'.'.$extension,
            'file_path' => 'diagnostics/results/'.fake()->uuid().'.'.$extension,
            'mime_type' => match ($extension) {
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                default => 'image/png',
            },
            'uploaded_by' => User::factory(),
        ];
    }
}
