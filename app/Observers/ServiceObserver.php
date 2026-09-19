<?php

namespace Modules\Diagnostics\Observers;

use Modules\Core\Models\Service;
use Modules\Core\Support\AppSettings;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;

/**
 * Gives every service placed in a lab / radiology / pathology category its
 * diagnostic setup automatically, so results can be recorded straight away.
 */
class ServiceObserver
{
    public function __construct(protected DiagnosticCatalogService $catalog) {}

    public function saved(Service $service): void
    {
        if (! $service->wasRecentlyCreated && ! $service->wasChanged('category_id')) {
            return;
        }

        if (! $this->diagnosticsEnabled()) {
            return;
        }

        $this->catalog->ensureProfileForService($service);
    }

    protected function diagnosticsEnabled(): bool
    {
        try {
            return (bool) app(AppSettings::class)->features()->diagnostics_enabled;
        } catch (\Throwable) {
            return true;
        }
    }
}
