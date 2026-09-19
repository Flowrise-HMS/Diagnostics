<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;

class EditDiagnosticServiceProfile extends EditRecord
{
    protected static string $resource = DiagnosticServiceProfileResource::class;

    /** @var list<array<string, mixed>> */
    protected array $resultFieldRows = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['result_fields'] = app(DiagnosticCatalogService::class)->resultFieldRows($this->getRecord());

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->resultFieldRows = array_values($data['result_fields'] ?? []);
        unset($data['result_fields']);

        return $data;
    }

    protected function afterSave(): void
    {
        app(DiagnosticCatalogService::class)->syncResultFields($this->getRecord(), $this->resultFieldRows);
    }
}
