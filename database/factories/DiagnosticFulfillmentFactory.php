<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clinical\Models\RequestItem;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticFulfillmentFactory extends Factory
{
    protected $model = DiagnosticFulfillment::class;

    public function definition(): array
    {
        $requestItem = RequestItem::factory()->create();

        return [
            'request_item_id' => $requestItem->id,
            'branch_id' => $requestItem->serviceRequest->branch_id,
            'discipline' => DiagnosticDiscipline::LAB,
            'accession_number' => fake()->optional()->bothify('ACC-########'),
            'status' => FulfillmentStatus::PENDING,
            'priority' => 'routine',
            'clinical_indication' => fake()->optional()->sentence(),
            'diagnosis_codes' => null,
            'metadata' => null,
        ];
    }

    public function forRequestItem(RequestItem $requestItem, string $discipline = 'lab'): static
    {
        return $this->state(fn () => [
            'request_item_id' => $requestItem->id,
            'branch_id' => $requestItem->serviceRequest->branch_id,
            'discipline' => $discipline,
        ]);
    }
}
