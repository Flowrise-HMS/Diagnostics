<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Diagnostics\Enums\AllocationStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticAllocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'allocations';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord instanceof DiagnosticFulfillment
            && ! $ownerRecord->discipline->supportsSchedulingWorkflow()) {
            return false;
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('resource_type')
                    ->label('Resource Type')
                    ->placeholder('room, device, staff')
                    ->required()
                    ->maxLength(255),
                TextInput::make('resource_id')
                    ->label('Resource ID')
                    ->required()
                    ->maxLength(255),
                DateTimePicker::make('scheduled_start')
                    ->required(),
                DateTimePicker::make('scheduled_end')
                    ->required(),
                Select::make('status')
                    ->options(AllocationStatus::class)
                    ->default(AllocationStatus::SCHEDULED)
                    ->required(),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('resource_type')
                    ->label('Resource Type')
                    ->badge(),
                TextColumn::make('resource_id')
                    ->label('Resource'),
                TextColumn::make('scheduled_start')
                    ->dateTime(),
                TextColumn::make('scheduled_end')
                    ->dateTime(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageAllocations', $this->getOwnerRecord()) ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageAllocations', $this->getOwnerRecord()) ?? false),
                DeleteAction::make(),
            ]);
    }
}
