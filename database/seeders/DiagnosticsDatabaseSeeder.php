<?php

namespace Modules\Diagnostics\Database\Seeders;

use Illuminate\Database\Seeder;

class DiagnosticsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DiagnosticsShieldPermissionsSeeder::class,
            DiagnosticsCustomPermissionSeeder::class,
            DiagnosticStarterCatalogSeeder::class,
        ]);
    }
}
