<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages;

use Modules\Core\Filament\Pages\Concerns\RestrictsActivitiesToSuperAdmin;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\DiagnosticResultTemplateResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListDiagnosticResultTemplateActivities extends ListActivitiesBySubject
{
    use RestrictsActivitiesToSuperAdmin;

    protected static string $resource = DiagnosticResultTemplateResource::class;
}
