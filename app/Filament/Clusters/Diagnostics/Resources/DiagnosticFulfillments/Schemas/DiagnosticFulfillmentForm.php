<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Diagnostics\Enums\FulfillmentStatus;

class DiagnosticFulfillmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fulfillment')
                    ->description('Operational status for the diagnostic order.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('request_item_id')
                                    ->relationship('requestItem', 'id')
                                    ->label('Request Item')
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('branch_id')
                                    ->relationship('branch', 'name')
                                    ->label('Branch')
                                    ->disabled()
                                    ->dehydrated(false),
                                TextInput::make('discipline')
                                    ->disabled()
                                    ->dehydrated(false),
                                Select::make('status')
                                    ->options(FulfillmentStatus::class)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
