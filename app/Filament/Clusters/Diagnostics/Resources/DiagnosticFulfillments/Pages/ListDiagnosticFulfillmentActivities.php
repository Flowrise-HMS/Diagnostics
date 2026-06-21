<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages;

use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListDiagnosticFulfillmentActivities extends ListActivities
{
    protected static string $resource = DiagnosticFulfillmentResource::class;
}
