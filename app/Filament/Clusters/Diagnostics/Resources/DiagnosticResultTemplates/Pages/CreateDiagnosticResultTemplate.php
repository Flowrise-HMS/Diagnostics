<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\DiagnosticResultTemplateResource;

class CreateDiagnosticResultTemplate extends CreateRecord
{
    protected static string $resource = DiagnosticResultTemplateResource::class;
}
