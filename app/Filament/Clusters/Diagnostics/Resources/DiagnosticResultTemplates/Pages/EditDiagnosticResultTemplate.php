<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\DiagnosticResultTemplateResource;

class EditDiagnosticResultTemplate extends EditRecord
{
    protected static string $resource = DiagnosticResultTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
