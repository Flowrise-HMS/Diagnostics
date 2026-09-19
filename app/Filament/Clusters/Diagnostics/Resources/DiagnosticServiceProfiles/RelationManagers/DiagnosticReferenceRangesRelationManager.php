<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

/**
 * Sex- and age-specific normal ranges for one numeric result field. The simple
 * low/high on the field itself covers most tests; these rows refine it per patient group.
 */
class DiagnosticReferenceRangesRelationManager extends RelationManager
{
    protected static string $relationship = 'referenceRanges';

    protected static ?string $title = 'Reference ranges by sex and age';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('template_field_id')
                    ->label('Result field')
                    ->options(fn (): array => $this->numericFieldOptions())
                    ->required()
                    ->columnSpanFull(),
                Select::make('gender')
                    ->label('Sex')
                    ->options([
                        'any' => 'Any',
                        'male' => 'Male',
                        'female' => 'Female',
                    ])
                    ->default('any')
                    ->required(),
                TextInput::make('units')
                    ->maxLength(50),
                TextInput::make('age_min_months')
                    ->label('Age from (months)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('age_max_months')
                    ->label('Age to (months)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('min_value')
                    ->label('Normal low')
                    ->numeric(),
                TextInput::make('max_value')
                    ->label('Normal high')
                    ->numeric(),
                TextInput::make('critical_low')
                    ->label('Critical low')
                    ->numeric(),
                TextInput::make('critical_high')
                    ->label('Critical high')
                    ->numeric(),
                Textarea::make('range_text')
                    ->label('Range text (shown instead of numbers)')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        $noNumericFields = $this->numericFieldOptions() === [];

        return $table
            ->columns([
                TextColumn::make('templateField.label')
                    ->label('Result field')
                    ->placeholder('Whole service'),
                TextColumn::make('gender')
                    ->label('Sex')
                    ->badge(),
                TextColumn::make('age_min_months')
                    ->label('Age from')
                    ->placeholder('-'),
                TextColumn::make('age_max_months')
                    ->label('Age to')
                    ->placeholder('-'),
                TextColumn::make('min_value')
                    ->label('Low')
                    ->placeholder('-'),
                TextColumn::make('max_value')
                    ->label('High')
                    ->placeholder('-'),
                TextColumn::make('units')
                    ->placeholder('-'),
                TextColumn::make('critical_low')
                    ->label('Crit. low')
                    ->placeholder('-'),
                TextColumn::make('critical_high')
                    ->label('Crit. high')
                    ->placeholder('-'),
            ])
            ->emptyStateHeading('No sex- or age-specific ranges')
            ->emptyStateDescription($noNumericFields
                ? 'Add a numeric result field to this service first, then define ranges for it here.'
                : 'The normal low/high on each result field applies to everyone until you add ranges here.')
            ->headerActions([
                CreateAction::make()
                    ->label('Add range')
                    ->disabled($noNumericFields)
                    ->authorize(fn (): bool => auth()->user()?->can('manageReferenceRanges', $this->getOwnerRecord()) ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageReferenceRanges', $this->getOwnerRecord()) ?? false),
                DeleteAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageReferenceRanges', $this->getOwnerRecord()) ?? false),
            ]);
    }

    /**
     * @return array<string, string>
     */
    protected function numericFieldOptions(): array
    {
        $profile = $this->getOwnerRecord();

        return $profile instanceof DiagnosticServiceProfile ? $profile->numericResultFieldOptions() : [];
    }
}
