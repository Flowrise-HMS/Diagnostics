<?php

namespace Modules\Diagnostics\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseTableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Reactive;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Tables\DiagnosticFulfillmentsTable;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Policies\DiagnosticFulfillmentPolicy;

class CompletedDiagnosticResultsWidget extends BaseTableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Completed Results';

    protected int|string|array $columnSpan = 'full';

    #[Reactive]
    public ?string $patientId = null;

    #[Reactive]
    public ?string $encounterId = null;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user !== null && app(DiagnosticFulfillmentPolicy::class)->viewAny($user);
    }

    protected function getTableQuery(): Builder
    {
        return DiagnosticFulfillment::query()
            ->where('status', FulfillmentStatus::COMPLETED)
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
        return 'No completed results';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Results appear here once a diagnostic request has been fulfilled.';
    }

    protected function getTablePollingInterval(): ?string
    {
        return null;
    }
}
