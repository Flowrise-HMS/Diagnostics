<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Database\Seeders\DiagnosticStarterCatalogSeeder;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers\DiagnosticReferenceRangesRelationManager;
use Modules\Diagnostics\Models\DiagnosticPanel;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Tests\TestCase;

class DiagnosticCatalogAdminTest extends TestCase
{
    use DatabaseTransactions;

    public function test_service_profile_resource_registers_only_the_reference_ranges_relation_manager(): void
    {
        $relations = DiagnosticServiceProfileResource::getRelations();

        $this->assertSame([DiagnosticReferenceRangesRelationManager::class], $relations);
    }

    public function test_service_profile_record_title_is_a_string(): void
    {
        $this->migrateModules();

        $profile = DiagnosticServiceProfile::factory()->create();

        $title = DiagnosticServiceProfileResource::getRecordTitle($profile);

        $this->assertIsString($title);
        $this->assertSame($profile->title, $title);
    }

    public function test_service_profile_can_ensure_panel_and_list_panel_items(): void
    {
        $this->migrateModules();

        $parent = DiagnosticServiceProfile::factory()->create();
        $child = DiagnosticServiceProfile::factory()->create();

        $panel = $parent->ensurePanel();

        DiagnosticPanelItem::factory()->create([
            'panel_id' => $panel->id,
            'child_profile_id' => $child->id,
            'sequence' => 1,
        ]);

        $this->assertCount(1, $parent->fresh()->panelItems);
        $this->assertTrue($parent->panel->is($panel));
    }

    public function test_starter_catalog_seeder_creates_field_scoped_ranges_and_modalities_without_component_services(): void
    {
        $this->migrateModules();

        $this->seed(DiagnosticStarterCatalogSeeder::class);

        $fbc = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Full Blood Count (FBC)'))
            ->first();

        $this->assertNotNull($fbc);
        $this->assertSame(['hemoglobin', 'pcv', 'wbc', 'platelets'], $fbc->resultFields()->pluck('field_key')->all());

        $hemoglobin = $fbc->resultFields()->where('diagnostic_result_template_fields.field_key', 'hemoglobin')->first();
        $this->assertSame(2, DiagnosticReferenceRange::query()->where('template_field_id', $hemoglobin->id)->count());
        $this->assertSame(0, DiagnosticReferenceRange::query()->whereNull('template_field_id')->count());

        $glucose = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Blood Glucose'))
            ->first();
        $glucoseField = $glucose->resultFields()->where('diagnostic_result_template_fields.field_key', 'glucose')->first();

        $this->assertDatabaseHas('diagnostic_reference_ranges', [
            'profile_id' => $glucose->id,
            'template_field_id' => $glucoseField->id,
            'units' => 'mg/dL',
        ]);

        // No hidden per-analyte services or panels are created any more.
        $this->assertFalse(Service::query()->withoutGlobalScope('branch')->where('name', 'Hemoglobin')->exists());
        $this->assertSame(0, DiagnosticPanel::query()->count());

        $chestXray = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Chest X-Ray'))
            ->first();

        $this->assertNotNull($chestXray);
        $this->assertSame('XR', $chestXray->modality);
        $this->assertSame('long_text', $chestXray->resultFields()->where('diagnostic_result_template_fields.field_key', 'findings')->value('value_type'));
    }
}
