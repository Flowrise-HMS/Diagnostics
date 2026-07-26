<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages;

use Modules\Core\Filament\Pages\Concerns\RestrictsActivitiesToSuperAdmin;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListDiagnosticFulfillmentActivities extends ListActivitiesBySubject
{
    use RestrictsActivitiesToSuperAdmin;

    protected static string $resource = DiagnosticFulfillmentResource::class;
}
