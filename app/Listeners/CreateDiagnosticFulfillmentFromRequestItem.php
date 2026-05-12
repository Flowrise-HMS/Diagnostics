<?php

namespace Modules\Diagnostics\Listeners;

use Modules\Clinical\Events\RequestItemCreated;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class CreateDiagnosticFulfillmentFromRequestItem
{
    public function handle(RequestItemCreated $event): void
    {
        $requestItem = $event->requestItem->loadMissing('serviceRequest');

        $profile = DiagnosticServiceProfile::query()
            ->where('service_id', $requestItem->service_id)
            ->where('is_active', true)
            ->first();

        if ($profile === null) {
            return;
        }

        DiagnosticFulfillment::query()->firstOrCreate(
            ['request_item_id' => $requestItem->id],
            [
                'branch_id' => $requestItem->serviceRequest->branch_id,
                'discipline' => $profile->discipline,
                'status' => 'pending',
            ]
        );
    }
}
