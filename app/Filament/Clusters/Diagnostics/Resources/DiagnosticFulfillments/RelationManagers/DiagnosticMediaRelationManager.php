<?php

namespace Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Diagnostics\Enums\StudyStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticStudy;

class DiagnosticMediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Study Media';

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
                    ->label('Media UID')
                    ->maxLength(255),
                TextInput::make('modality')
                    ->maxLength(64),
                TextInput::make('file_type')
                    ->required()
                    ->maxLength(64),
                TextInput::make('file_name')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('file_path')
                    ->label('File')
                    ->directory('diagnostics/media')
                    ->required(),
                TextInput::make('mime_type')
                    ->label('MIME Type'),
                TextInput::make('thumbnail_path')
                    ->label('Thumbnail Path'),
                TextInput::make('viewer_url')
                    ->label('Viewer URL')
                    ->url(),
                Toggle::make('is_key_image')
                    ->label('Key Image'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('study.modality')
                    ->label('Study Modality')
                    ->placeholder('-'),
                TextColumn::make('file_name')
                    ->searchable(),
                TextColumn::make('file_type')
                    ->badge(),
                TextColumn::make('modality')
                    ->badge()
                    ->placeholder('-'),
                IconColumn::make('is_key_image')
                    ->label('Key Image')
                    ->boolean(),
                TextColumn::make('viewer_url')
                    ->label('Viewer')
                    ->url(fn ($record): ?string => $record->viewer_url)
                    ->openUrlInNewTab()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('upload_diagnostic_result_file') ?? false)
                    ->mutateDataUsing(function (array $data): array {
                        $study = $this->resolveStudyForMedia(createIfMissing: true);

                        if ($study !== null) {
                            $data['study_id'] = $study->id;
                        }

                        if (! empty($data['file_path']) && empty($data['file_name'])) {
                            $data['file_name'] = basename((string) $data['file_path']);
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('upload_diagnostic_result_file') ?? false),
                DeleteAction::make(),
            ]);
    }

    protected function resolveStudyForMedia(bool $createIfMissing = false): ?DiagnosticStudy
    {
        $fulfillment = $this->getOwnerRecord();

        if (! $fulfillment instanceof DiagnosticFulfillment) {
            return null;
        }

        $study = $fulfillment->study;

        if ($study === null && $createIfMissing) {
            $study = $fulfillment->study()->create([
                'modality' => 'OT',
                'status' => StudyStatus::REGISTERED,
            ]);

            Notification::make()
                ->title('Imaging study registered for media upload.')
                ->info()
                ->send();
        }

        return $study;
    }
}
