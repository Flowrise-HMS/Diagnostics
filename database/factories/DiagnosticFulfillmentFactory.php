<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clinical\Models\RequestItem;
use Modules\Core\Enums\ServiceCategoryCode;
use Modules\Core\Models\Service;
use Modules\Core\Models\ServiceCategory;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticFulfillmentFactory extends Factory
{
    protected $model = DiagnosticFulfillment::class;

    public function definition(): array
    {
        // A non-diagnostic service, otherwise the auto-setup observer and the order bridge
        // would already have created a fulfillment for this request item.
        $service = Service::factory()->forCategory(
            ServiceCategory::query()->firstOrCreate(
                ['code' => ServiceCategoryCode::CON->value],
                ['name' => ServiceCategoryCode::CON->getLabel(), 'is_active' => true],
            ),
        )->create();

        $requestItem = RequestItem::factory()->forService($service)->create();

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
