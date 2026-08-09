<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Core\Enums\NavigationGroup;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\DiagnosticsCluster;
use Modules\Diagnostics\Settings\DiagnosticsSettings;

class ManageDiagnosticsSettings extends SettingsPage
{
    use HasPageShield;

    protected static ?string $cluster = DiagnosticsCluster::class;

    protected static string $settings = DiagnosticsSettings::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|\UnitEnum|null $navigationGroup = NavigationGroup::SETTINGS;

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Diagnostics';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Diagnostics'))
                    ->columns(2)
                    ->schema([
                        Select::make('default_report_status')
                            ->label(__('Default report status'))
                            ->options([
                                'preliminary' => __('Preliminary'),
                                'final' => __('Final'),
                                'amended' => __('Amended'),
                            ])
                            ->required(),
                        Toggle::make('auto_create_fulfillment')
                            ->label(__('Auto-create fulfillment from clinical requests')),
                        Toggle::make('workspace_entry_enabled')
                            ->label(__('Enable diagnostics workspace entry')),
                    ]),
            ]);
    }
}
