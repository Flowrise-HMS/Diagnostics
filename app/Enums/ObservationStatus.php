<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ObservationStatus: string implements HasColor, HasDescription, HasLabel
{
    case REGISTERED = 'registered';
    case PRELIMINARY = 'preliminary';
    case FINAL = 'final';
    case AMENDED = 'amended';
    case CANCELLED = 'cancelled';
    case ENTERED_IN_ERROR = 'entered-in-error';

    public function getLabel(): string
    {
        return match ($this) {
            self::REGISTERED => 'Registered',
            self::PRELIMINARY => 'Preliminary',
            self::FINAL => 'Final',
            self::AMENDED => 'Amended',
            self::CANCELLED => 'Cancelled',
            self::ENTERED_IN_ERROR => 'Entered in Error',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::REGISTERED => 'Observation has been registered but not yet resulted.',
            self::PRELIMINARY => 'Preliminary result available.',
            self::FINAL => 'Final verified result.',
            self::AMENDED => 'Result has been amended.',
            self::CANCELLED => 'Observation was cancelled.',
            self::ENTERED_IN_ERROR => 'Result was entered in error.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::REGISTERED => 'gray',
            self::PRELIMINARY => 'warning',
            self::FINAL => 'success',
            self::AMENDED => 'info',
            self::CANCELLED => 'gray',
            self::ENTERED_IN_ERROR => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
