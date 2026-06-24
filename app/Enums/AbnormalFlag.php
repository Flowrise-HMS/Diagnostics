<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AbnormalFlag: string implements HasColor, HasLabel
{
    case NORMAL = 'normal';
    case HIGH = 'high';
    case LOW = 'low';
    case CRITICALLY_HIGH = 'critically_high';
    case CRITICALLY_LOW = 'critically_low';
    case ABNORMAL = 'abnormal';
    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';

    public function getLabel(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
            self::LOW => 'Low',
            self::CRITICALLY_HIGH => 'Critically High',
            self::CRITICALLY_LOW => 'Critically Low',
            self::ABNORMAL => 'Abnormal',
            self::POSITIVE => 'Positive',
            self::NEGATIVE => 'Negative',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NORMAL, self::NEGATIVE => 'success',
            self::HIGH, self::LOW, self::ABNORMAL, self::POSITIVE => 'warning',
            self::CRITICALLY_HIGH, self::CRITICALLY_LOW => 'danger',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
