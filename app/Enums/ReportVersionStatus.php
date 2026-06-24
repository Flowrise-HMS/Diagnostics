<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ReportVersionStatus: string implements HasColor, HasDescription, HasLabel
{
    case PRELIMINARY = 'preliminary';
    case FINAL = 'final';
    case AMENDED = 'amended';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PRELIMINARY => 'Preliminary',
            self::FINAL => 'Final',
            self::AMENDED => 'Amended',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PRELIMINARY => 'Preliminary report version.',
            self::FINAL => 'Final signed report version.',
            self::AMENDED => 'Amended report version.',
            self::CANCELLED => 'Report version was cancelled.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PRELIMINARY => 'warning',
            self::FINAL => 'success',
            self::AMENDED => 'info',
            self::CANCELLED => 'gray',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
