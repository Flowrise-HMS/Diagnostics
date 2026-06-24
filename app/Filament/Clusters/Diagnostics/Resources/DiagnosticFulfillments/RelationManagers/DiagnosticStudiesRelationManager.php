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
use Modules\Diagnostics\Enums\StudyStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;

class DiagnosticStudiesRelationManager extends RelationManager
{
    protected static string $relationship = 'study';

    protected static ?string $title = 'Studies';

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
                TextInput::make('uid')
                    ->label('Study UID')
                    ->maxLength(255),
                TextInput::make('accession_number')
                    ->label('Accession Number')
                    ->maxLength(255),
                TextInput::make('modality')
                    ->required()
                    ->maxLength(64),
                TextInput::make('body_site')
                    ->maxLength(255),
                Select::make('status')
                    ->options(StudyStatus::class)
                    ->default(StudyStatus::REGISTERED)
                    ->required(),
                DateTimePicker::make('performed_at'),
                TextInput::make('number_of_series')
                    ->numeric()
                    ->default(0),
                Textarea::make('conclusion')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uid')
                    ->label('Study UID')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('modality')
                    ->badge(),
                TextColumn::make('body_site')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('performed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('media_count')
                    ->label('Media')
                    ->counts('media'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('Update DiagnosticFulfillment') ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('Update DiagnosticFulfillment') ?? false),
                DeleteAction::make(),
            ]);
    }
}
