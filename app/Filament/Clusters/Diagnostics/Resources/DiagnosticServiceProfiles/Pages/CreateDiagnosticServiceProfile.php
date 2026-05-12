<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;

class CreateDiagnosticServiceProfile extends CreateRecord
{
    protected static string $resource = DiagnosticServiceProfileResource::class;
}
