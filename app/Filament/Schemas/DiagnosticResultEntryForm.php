<?php

namespace Modules\Diagnostics\Filament\Schemas;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Modules\Clinical\Models\RequestItem;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;

class DiagnosticResultEntryForm
{
    /**
     * @return array<int, Component>
     */
    public static function components(RequestItem $item): array
    {
        $service = app(DiagnosticResultService::class);
        $profile = $service->getProfile($item);
        $templateFields = $profile ? $service->getTemplateFields($profile) : collect();

        $schema = [];

        if ($templateFields->isNotEmpty()) {
            foreach ($templateFields as $field) {
                $schema[] = self::fieldToComponent($field);
            }
        } else {
            $schema[] = Repeater::make('results')
                ->label('Results')
                ->schema([
                    TextInput::make('key')->label('Field')->required(),
                    TextInput::make('value')->label('Value')->required(),
                ])
                ->columns(2)
                ->defaultItems(0);
        }

        $schema[] = FileUpload::make('result_files')
            ->label('Result Files (PDF, Images)')
            ->multiple()
            ->directory('diagnostics/results')
            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->maxSize(10240);

        $schema[] = Textarea::make('notes')
            ->label('Notes')
            ->rows(2);

        return $schema;
    }

    protected static function fieldToComponent(DiagnosticResultTemplateField $field): TextInput|Select
    {
        $name = "field_{$field->field_key}";

        return match ($field->value_type) {
            'numeric' => TextInput::make($name)
                ->label($field->label)
                ->numeric()
                ->step('any'),
            'select' => Select::make($name)
                ->label($field->label)
                ->options(self::parseSelectOptions($field)),
            default => TextInput::make($name)
                ->label($field->label),
        };
    }

    /**
     * @return array<string, string>
     */
    protected static function parseSelectOptions(DiagnosticResultTemplateField $field): array
    {
        $options = $field->options;

        if (is_array($options)) {
            return array_combine($options, $options);
        }

        if (is_string($options)) {
            $parts = array_map('trim', explode(',', $options));

            return array_combine($parts, $parts);
        }

        return [];
    }
}
