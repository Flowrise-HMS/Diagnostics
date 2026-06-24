<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
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

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $icon = 'heroicon-o-clipboard-document-check';

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
