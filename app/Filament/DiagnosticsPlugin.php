<?php

namespace Modules\Diagnostics\Filament;

use Coolsam\Modules\Concerns\ModuleFilamentPlugin;
use Filament\Contracts\Plugin;
use Filament\Panel;

class DiagnosticsPlugin implements Plugin
{
    use ModuleFilamentPlugin {
        register as protected traitRegister;
    }

    public function getModuleName(): string
    {
        return 'Diagnostics';
    }

    public function getId(): string
    {
        return 'diagnostics';
    }

    public function register(Panel $panel): void
    {
        if (! $this->diagnosticsEnabled()) {
            return;
        }

        $this->traitRegister($panel);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    protected function diagnosticsEnabled(): bool
    {
        try {
            return app(\Modules\Core\Settings\FeatureSettings::class)->diagnostics_enabled;
        } catch (\Throwable) {
            return true;
        }
    }
}
