<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages;

use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\DiagnosticResultTemplateResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListDiagnosticResultTemplateActivities extends ListActivitiesBySubject
{
    protected static string $resource = DiagnosticResultTemplateResource::class;
}
