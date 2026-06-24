<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum StudyStatus: string implements HasColor, HasDescription, HasLabel
{
    case REGISTERED = 'registered';
    case AVAILABLE = 'available';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::REGISTERED => 'Registered',
            self::AVAILABLE => 'Available',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::REGISTERED => 'Imaging study has been registered.',
            self::AVAILABLE => 'Study images are available for review.',
            self::COMPLETED => 'Study interpretation is complete.',
            self::CANCELLED => 'Study was cancelled.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::REGISTERED => 'gray',
            self::AVAILABLE => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
