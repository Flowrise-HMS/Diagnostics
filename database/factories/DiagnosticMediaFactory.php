<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Models\DiagnosticMedia;
use Modules\Diagnostics\Models\DiagnosticStudy;

class DiagnosticMediaFactory extends Factory
{
    protected $model = DiagnosticMedia::class;

    public function definition(): array
    {
        $extension = fake()->randomElement(['jpg', 'png']);

        return [
            'study_id' => DiagnosticStudy::factory(),
            'file_type' => $extension,
            'file_name' => fake()->lexify('diagnostic-media-????').'.'.$extension,
            'file_path' => 'diagnostics/media/'.fake()->uuid().'.'.$extension,
        ];
    }
}
