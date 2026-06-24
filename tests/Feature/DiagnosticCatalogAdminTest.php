<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Modules\Diagnostics\Database\Seeders\DiagnosticStarterCatalogSeeder;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\DiagnosticServiceProfileResource;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers\DiagnosticPanelsRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers\DiagnosticReferenceRangesRelationManager;
use Modules\Diagnostics\Http\Requests\DiagnosticResultTemplateRequest;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Tests\TestCase;

class DiagnosticCatalogAdminTest extends TestCase
{
    use DatabaseTransactions;

    public function test_service_profile_resource_registers_catalog_relation_managers(): void
    {
        $relations = DiagnosticServiceProfileResource::getRelations();

        $this->assertContains(DiagnosticPanelsRelationManager::class, $relations);
        $this->assertContains(DiagnosticReferenceRangesRelationManager::class, $relations);
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

    public function test_result_template_request_validates_spec_aligned_field_names(): void
    {
        $this->migrateModules();

        $profile = DiagnosticServiceProfile::factory()->create();

        $validator = Validator::make([
            'profile_id' => $profile->id,
            'name' => 'Starter Template',
            'fields' => [
                [
                    'field_key' => 'hemoglobin',
                    'label' => 'Hemoglobin',
                    'value_type' => 'numeric',
                    'observation_code' => '718-7',
                    'default_units' => 'g/dL',
                    'is_required' => true,
                    'reference_range_low' => 12.0,
                    'reference_range_high' => 17.5,
                    'sort_order' => 1,
                ],
            ],
        ], (new DiagnosticResultTemplateRequest)->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_starter_catalog_seeder_creates_panels_reference_ranges_and_modalities(): void
    {
        $this->migrateModules();

        $this->seed(DiagnosticStarterCatalogSeeder::class);

        $fbc = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Full Blood Count (FBC)'))
            ->first();

        $this->assertNotNull($fbc);
        $this->assertCount(4, $fbc->panelItems);

        $hemoglobin = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Hemoglobin'))
            ->first();

        $this->assertNotNull($hemoglobin);
        $this->assertGreaterThanOrEqual(2, DiagnosticReferenceRange::query()->where('profile_id', $hemoglobin->id)->count());

        $glucose = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Blood Glucose'))
            ->first();

        $this->assertNotNull($glucose);
        $this->assertDatabaseHas('diagnostic_reference_ranges', [
            'profile_id' => $glucose->id,
            'units' => 'mg/dL',
        ]);

        $chestXray = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Chest X-Ray'))
            ->first();

        $this->assertNotNull($chestXray);
        $this->assertSame('XR', $chestXray->modality);
    }
}
