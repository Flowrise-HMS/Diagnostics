<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum DiagnosticDiscipline: string implements HasColor, HasDescription, HasLabel
{
    case LAB = 'lab';
    case RADIOLOGY = 'radiology';
    case PATHOLOGY = 'pathology';

    public function getLabel(): string
    {
        return match ($this) {
            self::LAB => 'Laboratory',
            self::RADIOLOGY => 'Radiology',
            self::PATHOLOGY => 'Pathology',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::LAB => 'Clinical laboratory diagnostics.',
            self::RADIOLOGY => 'Medical imaging and radiology.',
            self::PATHOLOGY => 'Anatomic and clinical pathology.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::LAB => 'info',
            self::RADIOLOGY => 'primary',
            self::PATHOLOGY => 'warning',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function supportsSpecimenWorkflow(): bool
    {
        return in_array($this, [self::LAB, self::PATHOLOGY], true);
    }

    public function supportsSchedulingWorkflow(): bool
    {
        return $this === self::RADIOLOGY;
    }
}
