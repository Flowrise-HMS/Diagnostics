<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticPanelsRelationManager extends RelationManager
{
    protected static string $relationship = 'panelItems';

    protected static ?string $title = 'Panel Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('child_profile_id')
                    ->label('Component Profile')
                    ->options(fn (): array => DiagnosticServiceProfile::query()
                        ->with('service')
                        ->where('id', '!=', $this->getOwnerRecord()->getKey())
                        ->orderBy('discipline')
                        ->get()
                        ->mapWithKeys(fn (DiagnosticServiceProfile $profile): array => [
                            $profile->id => ($profile->service?->name ?? 'Unknown Service').' ('.strtoupper($profile->discipline->value).')',
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('sequence')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('is_required')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sequence')
                    ->sortable(),
                TextColumn::make('childProfile.service.name')
                    ->label('Component Service')
                    ->searchable(),
                TextColumn::make('childProfile.discipline')
                    ->label('Discipline')
                    ->badge(),
                IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
            ])
            ->defaultSort('sequence')
            ->reorderable('sequence')
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('managePanels', $this->getOwnerRecord()) ?? false)
                    ->using(function (array $data): DiagnosticPanelItem {
                        $panel = $this->getOwnerRecord()->ensurePanel();

                        return DiagnosticPanelItem::query()->create([
                            'panel_id' => $panel->id,
                            'child_profile_id' => $data['child_profile_id'],
                            'sequence' => $data['sequence'] ?? 0,
                            'is_required' => $data['is_required'] ?? true,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('managePanels', $this->getOwnerRecord()) ?? false),
                DeleteAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('managePanels', $this->getOwnerRecord()) ?? false),
            ]);
    }
}
