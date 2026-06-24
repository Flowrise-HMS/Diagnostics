<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Diagnostics\Enums\SpecimenCondition;
use Modules\Diagnostics\Enums\SpecimenStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticSpecimensRelationManager extends RelationManager
{
    protected static string $relationship = 'specimens';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if ($ownerRecord instanceof DiagnosticFulfillment
            && ! $ownerRecord->discipline->supportsSpecimenWorkflow()) {
            return false;
        }

        return parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('accession_number')
                    ->label('Accession Number')
                    ->maxLength(255),
                TextInput::make('specimen_type')
                    ->required()
                    ->maxLength(255),
                TextInput::make('specimen_class')
                    ->maxLength(255),
                TextInput::make('collection_method')
                    ->maxLength(255),
                TextInput::make('body_site')
                    ->maxLength(255),
                TextInput::make('barcode')
                    ->maxLength(255),
                Select::make('status')
                    ->options(SpecimenStatus::class)
                    ->default(SpecimenStatus::COLLECTED)
                    ->required(),
                Select::make('condition')
                    ->options(SpecimenCondition::class)
                    ->nullable(),
                DateTimePicker::make('collected_at')
                    ->default(now()),
                DateTimePicker::make('received_at'),
                TextInput::make('storage_location')
                    ->maxLength(255),
                Textarea::make('condition_note')
                    ->rows(2)
                    ->columnSpanFull(),
                Repeater::make('containers')
                    ->relationship('containers')
                    ->schema([
                        TextInput::make('container_type')
                            ->required(),
                        TextInput::make('additive'),
                        TextInput::make('identifier'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Repeater::make('processingEvents')
                    ->relationship('processingEvents')
                    ->schema([
                        TextInput::make('procedure')
                            ->required(),
                        TextInput::make('additive'),
                        DateTimePicker::make('processed_at')
                            ->default(now()),
                        Textarea::make('description')
                            ->rows(2),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('accession_number')
                    ->label('Accession')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('specimen_type')
                    ->searchable(),
                TextColumn::make('barcode')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('condition')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('collected_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('containers_count')
                    ->label('Containers')
                    ->counts('containers'),
                TextColumn::make('processing_events_count')
                    ->label('Processing Events')
                    ->counts('processingEvents'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('collectSpecimen', $this->getOwnerRecord()) ?? false)
                    ->mutateDataUsing(function (array $data): array {
                        if (! isset($data['collected_by'])) {
                            $data['collected_by'] = auth()->id();
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageSpecimenProcessing', $this->getOwnerRecord()) ?? false),
                DeleteAction::make(),
            ]);
    }
}
