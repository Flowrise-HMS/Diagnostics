<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Modules\Core\Enums\NavigationGroup;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\DiagnosticsCluster;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages\CreateDiagnosticResultTemplate;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages\EditDiagnosticResultTemplate;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages\ListDiagnosticResultTemplateActivities;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages\ListDiagnosticResultTemplates;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Pages\ViewDiagnosticResultTemplate;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Schemas\DiagnosticResultTemplateForm;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Schemas\DiagnosticResultTemplateInfolist;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticResultTemplates\Tables\DiagnosticResultTemplatesTable;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;

class DiagnosticResultTemplateResource extends Resource
{
    protected static ?string $model = DiagnosticResultTemplate::class;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::DIAGNOSTICS;

    protected static ?string $cluster = DiagnosticsCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $icon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return DiagnosticResultTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiagnosticResultTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiagnosticResultTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiagnosticResultTemplates::route('/'),
            'create' => CreateDiagnosticResultTemplate::route('/create'),
            'view' => ViewDiagnosticResultTemplate::route('/{record}'),
            'edit' => EditDiagnosticResultTemplate::route('/{record}/edit'),
            'activities' => ListDiagnosticResultTemplateActivities::route('/{record}/activities'),
        ];
    }
}
