<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Core\Support\SuperAdmin;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\DiagnosticResultTemplateResource;

class ViewDiagnosticResultTemplate extends ViewRecord
{
    protected static string $resource = DiagnosticResultTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('activities')
                ->visible(fn (): bool => SuperAdmin::check())
                ->label('Activities')
                ->icon('heroicon-o-bell-alert')
                ->url(fn () => DiagnosticResultTemplateResource::getUrl('activities', ['record' => $this->getRecord()])),
        ];
    }
}
