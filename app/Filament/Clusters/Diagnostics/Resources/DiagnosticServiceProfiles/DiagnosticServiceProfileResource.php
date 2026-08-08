<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Enums\NavigationGroup;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\DiagnosticsCluster;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\CreateDiagnosticServiceProfile;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\EditDiagnosticServiceProfile;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ListDiagnosticServiceProfileActivities;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ListDiagnosticServiceProfiles;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ViewDiagnosticServiceProfile;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers\DiagnosticPanelsRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers\DiagnosticReferenceRangesRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Schemas\DiagnosticServiceProfileForm;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Schemas\DiagnosticServiceProfileInfolist;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Tables\DiagnosticServiceProfilesTable;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticServiceProfileResource extends Resource
{
    protected static ?string $model = DiagnosticServiceProfile::class;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::DIAGNOSTICS;

    protected static ?string $cluster = DiagnosticsCluster::class;

    protected static ?string $recordTitleAttribute = 'loinc_display';

    protected static ?string $icon = 'heroicon-o-beaker';

    /**
     * Keep the title attribute pointing at a plain string column: Filament reads it directly for
     * record titles and global search, and an enum-cast column breaks the expected return type.
     */
    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record?->title ?? static::getModelLabel();
    }

    public static function form(Schema $schema): Schema
    {
        return DiagnosticServiceProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiagnosticServiceProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiagnosticServiceProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DiagnosticPanelsRelationManager::class,
            DiagnosticReferenceRangesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiagnosticServiceProfiles::route('/'),
            'create' => CreateDiagnosticServiceProfile::route('/create'),
            'view' => ViewDiagnosticServiceProfile::route('/{record}'),
            'edit' => EditDiagnosticServiceProfile::route('/{record}/edit'),
            'activities' => ListDiagnosticServiceProfileActivities::route('/{record}/activities'),
        ];
    }
}
