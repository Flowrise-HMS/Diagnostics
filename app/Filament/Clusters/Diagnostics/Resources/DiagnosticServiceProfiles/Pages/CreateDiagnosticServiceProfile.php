<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;

class CreateDiagnosticServiceProfile extends CreateRecord
{
    protected static string $resource = DiagnosticServiceProfileResource::class;

    /** @var list<array<string, mixed>> */
    protected array $resultFieldRows = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->resultFieldRows = array_values($data['result_fields'] ?? []);
        unset($data['result_fields']);

        return $data;
    }

    protected function afterCreate(): void
    {
        app(DiagnosticCatalogService::class)->syncResultFields($this->getRecord(), $this->resultFieldRows);
    }
}
