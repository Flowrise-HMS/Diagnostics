<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages;

use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;

class EditDiagnosticFulfillment extends EditRecord
{
    protected static string $resource = DiagnosticFulfillmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
