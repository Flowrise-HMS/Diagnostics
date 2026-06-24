<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Diagnostics\Enums\AllocationStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticFulfillmentAllocation;

class DiagnosticFulfillmentAllocationFactory extends Factory
{
    protected $model = DiagnosticFulfillmentAllocation::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('now', '+3 days');

        return [
            'fulfillment_id' => DiagnosticFulfillment::factory(),
            'resource_type' => fake()->randomElement(['room', 'device', 'staff']),
            'resource_id' => fake()->uuid(),
            'scheduled_start' => $start,
            'scheduled_end' => (clone $start)->modify('+1 hour'),
            'status' => AllocationStatus::SCHEDULED,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
