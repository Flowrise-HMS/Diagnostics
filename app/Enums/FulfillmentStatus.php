<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum FulfillmentStatus: string implements HasColor, HasDescription, HasLabel
{
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case COLLECTED = 'collected';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SCHEDULED => 'Scheduled',
            self::COLLECTED => 'Collected',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PENDING => 'Awaiting work to begin.',
            self::SCHEDULED => 'Booked for later execution.',
            self::COLLECTED => 'Sample or source material has been collected.',
            self::IN_PROGRESS => 'Diagnostic work is actively underway.',
            self::COMPLETED => 'Diagnostic work has been completed.',
            self::CANCELLED => 'Diagnostic work was cancelled.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::SCHEDULED => 'info',
            self::COLLECTED => 'primary',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'gray',
        };
    }
}
