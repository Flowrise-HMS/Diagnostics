<?php

namespace Modules\Diagnostics\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FileSourceType: string implements HasColor, HasLabel
{
    case INTERNAL_ENTRY = 'internal_entry';
    case EXTERNAL_LAB = 'external_lab';
    case EXTERNAL_RADIOLOGY = 'external_radiology';
    case EXTERNAL_FACILITY = 'external_facility';
    case SCANNED = 'scanned';
    case MANUAL_UPLOAD = 'manual_upload';
    case DICOM_IMPORT = 'dicom_import';
    case HL7_IMPORT = 'hl7_import';

    public function getLabel(): string
    {
        return match ($this) {
            self::INTERNAL_ENTRY => 'Internal Entry',
            self::EXTERNAL_LAB => 'External Lab',
            self::EXTERNAL_RADIOLOGY => 'External Radiology',
            self::EXTERNAL_FACILITY => 'External Facility',
            self::SCANNED => 'Scanned Document',
            self::MANUAL_UPLOAD => 'Manual Upload',
            self::DICOM_IMPORT => 'DICOM Import',
            self::HL7_IMPORT => 'HL7 Import',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::INTERNAL_ENTRY => 'primary',
            self::EXTERNAL_LAB, self::EXTERNAL_RADIOLOGY, self::EXTERNAL_FACILITY => 'info',
            self::SCANNED, self::MANUAL_UPLOAD => 'gray',
            self::DICOM_IMPORT => 'warning',
            self::HL7_IMPORT => 'success',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
