<?php

namespace Modules\Diagnostics\Settings;

use Spatie\LaravelSettings\Settings;

class DiagnosticsSettings extends Settings
{
    public string $default_report_status = 'final';

    public bool $auto_create_fulfillment = true;

    public bool $workspace_entry_enabled = true;

    public static function group(): string
    {
        return 'diagnostics';
    }
}
