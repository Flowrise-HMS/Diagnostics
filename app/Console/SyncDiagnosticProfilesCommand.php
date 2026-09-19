<?php

namespace Modules\Diagnostics\Console;

use Illuminate\Console\Command;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;

class SyncDiagnosticProfilesCommand extends Command
{
    protected $signature = 'diagnostics:sync-profiles';

    protected $description = 'Create the diagnostic setup for active services in lab, radiology and pathology categories that have none';

    public function handle(DiagnosticCatalogService $catalog): int
    {
        $result = $catalog->syncProfilesFromServiceCatalog();

        $this->info("Created {$result['created']} diagnostic service(s); {$result['existing']} already set up.");

        return self::SUCCESS;
    }
}
