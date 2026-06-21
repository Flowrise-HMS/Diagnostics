<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages;

use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;
use pxlrbt\FilamentActivityLog\Pages\ListActivities;

class ListDiagnosticServiceProfileActivities extends ListActivities
{
    protected static string $resource = DiagnosticServiceProfileResource::class;
}
