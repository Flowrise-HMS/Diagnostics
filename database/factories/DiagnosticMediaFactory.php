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
            'uid' => fake()->optional()->uuid(),
            'series_uid' => fake()->optional()->uuid(),
            'series_number' => fake()->optional()->numberBetween(1, 10),
            'instance_number' => fake()->optional()->numberBetween(1, 100),
            'modality' => fake()->optional()->randomElement(['CT', 'MR', 'US', 'XR']),
            'file_type' => $extension,
            'file_name' => fake()->lexify('diagnostic-media-????').'.'.$extension,
            'file_path' => 'diagnostics/media/'.fake()->uuid().'.'.$extension,
            'mime_type' => match ($extension) {
                'jpg' => 'image/jpeg',
                default => 'image/png',
            },
            'is_key_image' => false,
        ];
    }
}
