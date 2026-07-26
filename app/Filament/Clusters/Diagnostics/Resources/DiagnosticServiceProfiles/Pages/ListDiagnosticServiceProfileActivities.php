<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages;

use Modules\Core\Filament\Pages\Concerns\RestrictsActivitiesToSuperAdmin;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListDiagnosticServiceProfileActivities extends ListActivitiesBySubject
{
    use RestrictsActivitiesToSuperAdmin;

    protected static string $resource = DiagnosticServiceProfileResource::class;
}
