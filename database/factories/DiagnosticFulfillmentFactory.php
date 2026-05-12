<?php

namespace Modules\Diagnostics\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clinical\Database\Factories\RequestItemFactory;
use Modules\Clinical\Models\RequestItem;
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
            'discipline' => 'lab',
            'status' => 'pending',
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
