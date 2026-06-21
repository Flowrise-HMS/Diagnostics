<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages;

use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\DiagnosticResultTemplateResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListDiagnosticResultTemplateActivities extends ListActivities
{
    protected static string $resource = DiagnosticResultTemplateResource::class;
}
