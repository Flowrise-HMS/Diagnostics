<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;

class ViewDiagnosticFulfillment extends ViewRecord
{
    protected static string $resource = DiagnosticFulfillmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
