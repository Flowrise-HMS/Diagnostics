<?php

namespace Modules\Diagnostics\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class DiagnosticsPlugin implements Plugin
{
    use ModuleFilamentPlugin;

    public function getModuleName(): string
    {
        return 'Diagnostics';
    }

    public function getId(): string
    {
        return 'diagnostics';
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }
}
