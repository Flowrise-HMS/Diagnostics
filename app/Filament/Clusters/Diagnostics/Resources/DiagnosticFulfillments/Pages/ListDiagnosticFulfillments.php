<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;

class ListDiagnosticFulfillments extends ListRecords
{
    protected static string $resource = DiagnosticFulfillmentResource::class;
}
