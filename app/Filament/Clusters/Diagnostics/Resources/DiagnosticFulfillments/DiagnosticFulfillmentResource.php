<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Enums\NavigationGroup;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\DiagnosticsCluster;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\EditDiagnosticFulfillment;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ListDiagnosticFulfillmentActivities;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ListDiagnosticFulfillments;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ViewDiagnosticFulfillment;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticAllocationsRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticMediaRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticObservationsRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticReportVersionsRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticResultFilesRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticSpecimensRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticStudiesRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Schemas\DiagnosticFulfillmentForm;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Schemas\DiagnosticFulfillmentInfolist;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Tables\DiagnosticFulfillmentsTable;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticFulfillmentResource extends Resource
{
    protected static ?string $model = DiagnosticFulfillment::class;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::DIAGNOSTICS;

    protected static ?string $cluster = DiagnosticsCluster::class;

    protected static ?string $recordTitleAttribute = 'accession_number';

    protected static ?string $icon = 'heroicon-o-clipboard-document-check';

    /**
     * Keep the title attribute pointing at a plain string column: Filament reads it directly for
     * record titles and global search, and an enum-cast column breaks the expected return type.
     */
    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record?->title ?? static::getModelLabel();
    }

    /**
     * The tables and infolists read through the request item to the service and client, so those
     * relationships are loaded up front for every page and record resolution. Report observations
     * are included because the print action evaluates printability for every table row.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'branch',
            'latestReportVersion.observations',
            'requestItem.service',
            'requestItem.serviceRequest.patient',
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return DiagnosticFulfillmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiagnosticFulfillmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiagnosticFulfillmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DiagnosticSpecimensRelationManager::class,
            DiagnosticObservationsRelationManager::class,
            DiagnosticStudiesRelationManager::class,
            DiagnosticMediaRelationManager::class,
            DiagnosticAllocationsRelationManager::class,
            DiagnosticReportVersionsRelationManager::class,
            DiagnosticResultFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiagnosticFulfillments::route('/'),
            'view' => ViewDiagnosticFulfillment::route('/{record}'),
            'edit' => EditDiagnosticFulfillment::route('/{record}/edit'),
            'activities' => ListDiagnosticFulfillmentActivities::route('/{record}/activities'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
