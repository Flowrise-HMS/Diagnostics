<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SpecimenCondition: string implements HasColor, HasLabel
{
    case ACCEPTABLE = 'acceptable';
    case HEMOLYZED = 'hemolyzed';
    case CLOTTED = 'clotted';
    case INSUFFICIENT = 'insufficient';
    case CONTAMINATED = 'contaminated';
    case LEAKING = 'leaking';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACCEPTABLE => 'Acceptable',
            self::HEMOLYZED => 'Hemolyzed',
            self::CLOTTED => 'Clotted',
            self::INSUFFICIENT => 'Insufficient',
            self::CONTAMINATED => 'Contaminated',
            self::LEAKING => 'Leaking',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACCEPTABLE => 'success',
            self::HEMOLYZED => 'warning',
            self::CLOTTED => 'warning',
            self::INSUFFICIENT => 'danger',
            self::CONTAMINATED => 'danger',
            self::LEAKING => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
