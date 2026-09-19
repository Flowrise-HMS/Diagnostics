<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasLabel;

enum ResultFieldValueType: string implements HasLabel
{
    case Numeric = 'numeric';
    case Text = 'text';
    case LongText = 'long_text';
    case Select = 'select';

    public function getLabel(): string
    {
        return match ($this) {
            self::Numeric => 'Number',
            self::Text => 'Short text',
            self::LongText => 'Long text',
            self::Select => 'Choice list',
        };
    }
}
