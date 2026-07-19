<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages;

use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivitiesBySubject;

class ListDiagnosticServiceProfileActivities extends ListActivitiesBySubject
{
    protected static string $resource = DiagnosticServiceProfileResource::class;
}
