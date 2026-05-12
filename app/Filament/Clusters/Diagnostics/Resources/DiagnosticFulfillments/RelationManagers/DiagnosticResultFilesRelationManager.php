<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiagnosticResultFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'resultFiles';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('report_version_id')
                    ->label('Report Version')
                    ->options(fn (): array => $this->getOwnerRecord()->reportVersions()->pluck('version', 'id')->map(fn (int $version): string => "Version {$version}")->all())
                    ->searchable()
                    ->nullable(),
                Select::make('source')
                    ->options([
                        'internal_entry' => 'Internal Entry',
                        'external_lab' => 'External Lab',
                        'external_facility' => 'External Facility',
                    ])
                    ->required(),
                Select::make('file_type')
                    ->options([
                        'pdf' => 'PDF',
                        'docx' => 'DOCX',
                        'jpg' => 'JPG',
                        'png' => 'PNG',
                    ])
                    ->required(),
                TextInput::make('file_name')
                    ->required(),
                FileUpload::make('file_path')
                    ->label('Result File')
                    ->directory('diagnostics/results')
                    ->required(),
                TextInput::make('mime_type')
                    ->label('MIME Type')
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->searchable(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('file_type')
                    ->badge(),
                TextColumn::make('reportVersion.version')
                    ->label('Report Version')
                    ->formatStateUsing(fn (?int $state): string => $state ? "Version {$state}" : '-'),
                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('upload_diagnostic_result_file') ?? false)
                    ->mutateDataUsing(function (array $data): array {
                        $data['uploaded_by'] = auth()->id();
                        $data['file_name'] = $data['file_name'] ?: basename((string) $data['file_path']);

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('upload_diagnostic_result_file') ?? false),
                DeleteAction::make(),
            ]);
    }
}
