<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Modules\Core\Support\SuperAdmin;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;

class ListDiagnosticServiceProfiles extends ListRecords
{
    protected static string $resource = DiagnosticServiceProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncFromCatalog')
                ->label('Sync from service catalog')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => SuperAdmin::check())
                ->tooltip('Gives every active laboratory, radiology or pathology service without a diagnostic setup one. Existing setups and service prices are never changed.')
                ->action(function (DiagnosticCatalogService $catalog): void {
                    $result = $catalog->syncProfilesFromServiceCatalog();

                    Notification::make()
                        ->title('Diagnostic services synced')
                        ->body("{$result['created']} created, {$result['existing']} already set up.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
