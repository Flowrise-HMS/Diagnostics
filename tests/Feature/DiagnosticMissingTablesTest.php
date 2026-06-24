<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Tests\TestCase;

class DiagnosticMissingTablesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules();
    }

    public function test_missing_design_spec_tables_exist(): void
    {
        $tables = [
            'diagnostic_panels',
            'diagnostic_panel_items',
            'diagnostic_reference_ranges',
            'diagnostic_observation_components',
            'diagnostic_specimen_containers',
            'diagnostic_specimen_processing_events',
            'diagnostic_fulfillment_allocations',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertFalse(Schema::hasTable('diagnostic_hl7_messages'));
    }

    public function test_fulfillment_has_composite_accession_unique_index(): void
    {
        $indexes = Schema::getIndexes('diagnostic_fulfillments');
        $composite = collect($indexes)->first(
            fn (array $index): bool => in_array('branch_id', $index['columns'], true)
                && in_array('accession_number', $index['columns'], true)
                && ($index['unique'] ?? false)
        );

        $this->assertNotNull($composite);
    }

    public function test_expanded_fulfillment_columns_exist(): void
    {
        foreach (['accession_number', 'priority', 'clinical_indication', 'deleted_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('diagnostic_fulfillments', $column), "Missing column: {$column}");
        }
    }

    public function test_fulfillment_factory_creates_with_soft_deletes_column(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();

        $this->assertNull($fulfillment->deleted_at);
    }
}
