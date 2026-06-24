<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum SpecimenStatus: string implements HasColor, HasDescription, HasLabel
{
    case COLLECTED = 'collected';
    case RECEIVED = 'received';
    case PROCESSING = 'processing';
    case STORED = 'stored';
    case DISPOSED = 'disposed';

    public function getLabel(): string
    {
        return match ($this) {
            self::COLLECTED => 'Collected',
            self::RECEIVED => 'Received',
            self::PROCESSING => 'Processing',
            self::STORED => 'Stored',
            self::DISPOSED => 'Disposed',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::COLLECTED => 'Specimen has been collected.',
            self::RECEIVED => 'Specimen received in the laboratory.',
            self::PROCESSING => 'Specimen is being processed.',
            self::STORED => 'Specimen is in storage.',
            self::DISPOSED => 'Specimen has been disposed.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::COLLECTED => 'info',
            self::RECEIVED => 'primary',
            self::PROCESSING => 'warning',
            self::STORED => 'success',
            self::DISPOSED => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
