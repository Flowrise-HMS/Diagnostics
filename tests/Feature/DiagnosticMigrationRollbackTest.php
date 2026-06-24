<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiagnosticMigrationRollbackTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules();
    }

    public function test_fulfillment_expand_migration_is_reversible(): void
    {
        $migration = '2026_06_24_100010_expand_diagnostic_fulfillments_table';
        $path = "Modules/Diagnostics/database/migrations/{$migration}.php";

        try {
            Artisan::call('migrate:rollback', ['--path' => $path, '--force' => true]);
            $this->assertFalse(Schema::hasColumn('diagnostic_fulfillments', 'accession_number'));

            Artisan::call('migrate', ['--path' => $path, '--force' => true]);
            $this->assertTrue(Schema::hasColumn('diagnostic_fulfillments', 'accession_number'));
        } finally {
            Artisan::call('migrate', ['--path' => $path, '--force' => true]);
        }
    }
}
