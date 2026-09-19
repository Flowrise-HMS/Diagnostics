<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Modules\Diagnostics\Enums\FileSourceType;
use Modules\Diagnostics\Models\DiagnosticResultFile;

class DiagnosticResultFilesRelationManager extends RelationManager
{
    protected static string $relationship = 'resultFiles';

    protected static ?string $title = 'Result files';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('file_path')
                    ->label('File')
                    ->disk(config('diagnostics.result_files.disk'))
                    ->directory(config('diagnostics.result_files.directory'))
                    ->visibility('private')
                    ->storeFileNamesIn('original_file_name')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(10240)
                    ->previewable()
                    ->openable()
                    ->downloadable()
                    ->required()
                    ->columnSpanFull(),
                Hidden::make('original_file_name'),
                Select::make('source')
                    ->options(FileSourceType::class)
                    ->default(FileSourceType::MANUAL_UPLOAD)
                    ->required(),
                Select::make('report_version_id')
                    ->label('Report version')
                    ->options(fn (): array => $this->getOwnerRecord()->reportVersions()->pluck('version', 'id')->map(fn (int $version): string => "Version {$version}")->all())
                    ->searchable()
                    ->nullable(),
                TextInput::make('file_name')
                    ->label('Display name')
                    ->helperText('Defaults to the uploaded file name.')
                    ->maxLength(255),
                Textarea::make('notes')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('source')
                    ->badge(),
                TextColumn::make('file_type')
                    ->label('Type')
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn (?int $state): string => $state ? Number::fileSize($state) : '-'),
                TextColumn::make('reportVersion.version')
                    ->label('Report version')
                    ->formatStateUsing(fn (?int $state): string => $state ? "Version {$state}" : '-'),
                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded by')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload file')
                    ->authorize(fn (): bool => auth()->user()?->can('upload_diagnostic_result_file') ?? false)
                    ->mutateDataUsing(fn (array $data): array => $this->describeStoredFile($data)),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->visible(fn (DiagnosticResultFile $record): bool => $record->canOpenInline())
                    ->url(fn (DiagnosticResultFile $record): ?string => $record->inlineUrl())
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn (DiagnosticResultFile $record): ?string => $record->downloadUrl())
                    ->openUrlInNewTab(),
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('upload_diagnostic_result_file') ?? false)
                    ->mutateDataUsing(fn (array $data): array => $this->describeStoredFile($data)),
                DeleteAction::make(),
            ]);
    }

    /**
     * Fill the descriptive columns from the stored file so operators only pick a file.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function describeStoredFile(array $data): array
    {
        $path = (string) ($data['file_path'] ?? '');
        $originalNames = (array) ($data['original_file_name'] ?? []);
        unset($data['original_file_name']);

        if ($path === '') {
            return $data;
        }

        $disk = Storage::disk(config('diagnostics.result_files.disk'));

        $data['uploaded_by'] ??= auth()->id();
        $data['file_name'] = filled($data['file_name'] ?? null)
            ? $data['file_name']
            : ($originalNames[$path] ?? (is_string($originalNames) ? $originalNames : null) ?? basename($path));
        $data['file_type'] = pathinfo($path, PATHINFO_EXTENSION) ?: null;

        if ($disk->exists($path)) {
            $data['mime_type'] = $disk->mimeType($path) ?: null;
            $data['file_size'] = $disk->size($path) ?: null;
        }

        return $data;
    }
}
