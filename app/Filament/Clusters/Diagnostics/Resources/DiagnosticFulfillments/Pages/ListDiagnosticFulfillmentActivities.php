<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages;

use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListDiagnosticFulfillmentActivities extends ListActivitiesBySubject
{
    protected static string $resource = DiagnosticFulfillmentResource::class;
}
