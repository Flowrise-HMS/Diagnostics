<?php

namespace Modules\Diagnostics\Listeners;

use Modules\Clinical\Events\RequestItemCancelled;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class CancelDiagnosticFulfillmentFromRequestItem
{
    public function handle(RequestItemCancelled $event): void
    {
        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $event->requestItem->id)
            ->first();

        if ($fulfillment === null || $fulfillment->status === FulfillmentStatus::CANCELLED) {
            return;
        }

        $fulfillment->cancel('Request item was cancelled.');
    }
}
