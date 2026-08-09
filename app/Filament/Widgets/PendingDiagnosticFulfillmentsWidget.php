<?php

namespace Modules\Diagnostics\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Reactive;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Tables\DiagnosticFulfillmentsTable;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class PendingDiagnosticFulfillmentsWidget extends BaseTableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Pending Diagnostics';

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'clinical::filament.widgets.collapsible-table-widget';

    #[Reactive]
    public ?string $patientId = null;

    #[Reactive]
    public ?string $encounterId = null;

    /**
     * @return list<FulfillmentStatus>
     */
    public static function activeStatuses(): array
    {
        return [
            FulfillmentStatus::PENDING,
            FulfillmentStatus::SCHEDULED,
            FulfillmentStatus::COLLECTED,
            FulfillmentStatus::IN_PROGRESS,
        ];
    }

    protected function getTableQuery(): Builder
    {
        return DiagnosticFulfillment::query()
            ->whereIn('status', self::activeStatuses())
            ->when(
                filled($this->patientId),
                fn (Builder $query): Builder => $query->whereHas(
                    'requestItem.serviceRequest',
                    fn (Builder $serviceRequest): Builder => $serviceRequest->where('patient_id', $this->patientId),
                ),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->when(
                filled($this->encounterId),
                fn (Builder $query): Builder => $query->whereHas(
                    'requestItem.serviceRequest',
                    fn (Builder $serviceRequest): Builder => $serviceRequest->where('encounter_id', $this->encounterId),
                ),
            )
            ->with([
                'branch',
                'latestReportVersion',
                'requestItem.service',
                'requestItem.serviceRequest.patient',
            ])
            ->latest('created_at');
    }

    protected function getTableColumns(): array
    {
        return DiagnosticFulfillmentsTable::columns();
    }

    protected function getTableFilters(): array
    {
        return DiagnosticFulfillmentsTable::filters(includeStatus: false);
    }

    protected function getTableActions(): array
    {
        return DiagnosticFulfillmentsTable::recordActions(includeMutations: false);
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No pending diagnostics';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Open diagnostic requests for this patient will appear here until results are completed.';
    }

    protected function getTablePollingInterval(): ?string
    {
        return null;
    }
}
