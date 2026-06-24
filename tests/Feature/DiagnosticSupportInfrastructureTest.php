<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Database\Seeders\DiagnosticsDatabaseSeeder;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticFulfillmentAllocation;
use Modules\Diagnostics\Models\DiagnosticMedia;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticObservationComponent;
use Modules\Diagnostics\Models\DiagnosticPanel;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticReportVersion;
use Modules\Diagnostics\Models\DiagnosticResultFile;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticSpecimen;
use Modules\Diagnostics\Models\DiagnosticSpecimenContainer;
use Modules\Diagnostics\Models\DiagnosticSpecimenProcessingEvent;
use Modules\Diagnostics\Models\DiagnosticStudy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DiagnosticSupportInfrastructureTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules();
    }

    public function test_diagnostics_factories_can_build_connected_records(): void
    {
        $profile = DiagnosticServiceProfile::factory()->create();
        $template = DiagnosticResultTemplate::factory()->create([
            'profile_id' => $profile->id,
        ]);
        $field = DiagnosticResultTemplateField::factory()->create([
            'template_id' => $template->id,
        ]);

        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => $profile->discipline,
        ]);
        $specimen = DiagnosticSpecimen::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);
        $observation = DiagnosticObservation::factory()->create([
            'fulfillment_id' => $fulfillment->id,
            'specimen_id' => $specimen->id,
        ]);
        $reportVersion = DiagnosticReportVersion::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);
        $reportVersion->observations()->attach($observation->id, ['sort_order' => 1]);

        $study = DiagnosticStudy::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);
        $media = DiagnosticMedia::factory()->create([
            'study_id' => $study->id,
        ]);
        $resultFile = DiagnosticResultFile::factory()->create([
            'fulfillment_id' => $fulfillment->id,
            'report_version_id' => $reportVersion->id,
        ]);

        $this->assertTrue($template->profile->is($profile));
        $this->assertTrue($field->template->is($template));
        $this->assertTrue($specimen->fulfillment->is($fulfillment));
        $this->assertTrue($observation->specimen->is($specimen));
        $this->assertCount(1, $reportVersion->fresh()->observations);
        $this->assertTrue($media->study->is($study));
        $this->assertTrue($resultFile->reportVersion->is($reportVersion));
    }

    public function test_catalog_panel_reference_range_and_allocation_factories(): void
    {
        $profile = DiagnosticServiceProfile::factory()->create();
        $panel = DiagnosticPanel::factory()->create(['profile_id' => $profile->id]);
        $item = DiagnosticPanelItem::factory()->create(['panel_id' => $panel->id]);
        $range = DiagnosticReferenceRange::factory()->create(['profile_id' => $profile->id]);
        $fulfillment = DiagnosticFulfillment::factory()->create();
        $allocation = DiagnosticFulfillmentAllocation::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);
        $observation = DiagnosticObservation::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);
        $component = DiagnosticObservationComponent::factory()->create([
            'observation_id' => $observation->id,
        ]);
        $container = DiagnosticSpecimenContainer::factory()->create([
            'specimen_id' => DiagnosticSpecimen::factory()->create([
                'fulfillment_id' => $fulfillment->id,
            ])->id,
        ]);
        $processingEvent = DiagnosticSpecimenProcessingEvent::factory()->create([
            'specimen_id' => $container->specimen_id,
        ]);

        $this->assertTrue($panel->profile->is($profile));
        $this->assertTrue($item->panel->is($panel));
        $this->assertTrue($range->profile->is($profile));
        $this->assertTrue($allocation->fulfillment->is($fulfillment));
        $this->assertTrue($component->observation->is($observation));
        $this->assertTrue($container->specimen->is($processingEvent->specimen));
    }

    public function test_diagnostics_database_seeder_creates_and_assigns_custom_workflow_permissions(): void
    {
        foreach ([
            'super_admin',
            'laboratory_technician',
            'laboratory_scientist',
            'radiology_technician',
            'radiologist',
            'pathologist',
        ] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        // Create the permissions the seeder assigns but does not create
        foreach ([
            'collect_diagnostic_specimen',
            'upload_diagnostic_result_file',
            'finalize_diagnostic_result',
            'verify_diagnostic_result',
            'sign_diagnostic_report',
            'amend_diagnostic_report',
            'assign_diagnostic_fulfillment',
            'ViewAny DiagnosticFulfillment',
            'View DiagnosticFulfillment',
            'Create DiagnosticFulfillment',
            'Update DiagnosticFulfillment',
            'Delete DiagnosticFulfillment',
            'Restore DiagnosticFulfillment',
            'ForceDelete DiagnosticFulfillment',
            'ForceDeleteAny DiagnosticFulfillment',
            'RestoreAny DiagnosticFulfillment',
            'Replicate DiagnosticFulfillment',
            'Reorder DiagnosticFulfillment',
            'ViewAny DiagnosticServiceProfile',
            'View DiagnosticServiceProfile',
            'Create DiagnosticServiceProfile',
            'Update DiagnosticServiceProfile',
            'Delete DiagnosticServiceProfile',
            'Restore DiagnosticServiceProfile',
            'ForceDelete DiagnosticServiceProfile',
            'ForceDeleteAny DiagnosticServiceProfile',
            'RestoreAny DiagnosticServiceProfile',
            'Replicate DiagnosticServiceProfile',
            'Reorder DiagnosticServiceProfile',
            'ViewAny DiagnosticResultTemplate',
            'View DiagnosticResultTemplate',
            'Create DiagnosticResultTemplate',
            'Update DiagnosticResultTemplate',
            'Delete DiagnosticResultTemplate',
            'Restore DiagnosticResultTemplate',
            'ForceDelete DiagnosticResultTemplate',
            'ForceDeleteAny DiagnosticResultTemplate',
            'RestoreAny DiagnosticResultTemplate',
            'Replicate DiagnosticResultTemplate',
            'Reorder DiagnosticResultTemplate',
            'View DiagnosticsCluster',
        ] as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        $this->seed(DiagnosticsDatabaseSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'name' => 'collect_diagnostic_specimen',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'ViewAny DiagnosticFulfillment',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'ViewAny DiagnosticServiceProfile',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'ViewAny DiagnosticResultTemplate',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'View DiagnosticsCluster',
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'sign_diagnostic_report',
            'guard_name' => 'web',
        ]);

        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('collect_diagnostic_specimen'));
        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('ViewAny DiagnosticFulfillment'));
        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('upload_diagnostic_result_file'));
        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('sign_diagnostic_report'));
        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('verify_diagnostic_result'));
        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('record_structured_diagnostic_observations'));
        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('print_diagnostic_lab_result'));
        $this->assertTrue(Role::findByName('super_admin', 'web')->hasPermissionTo('manage_diagnostic_panels'));
        $this->assertTrue(Role::findByName('super_admin', 'web')->hasPermissionTo('manage_diagnostic_reference_ranges'));
        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('manage_diagnostic_allocations'));
        $this->assertTrue(Role::findByName('laboratory_technician', 'web')->hasPermissionTo('manage_diagnostic_specimen_processing'));
    }

    public function test_diagnostics_database_seeder_adds_small_clinic_starter_catalog_without_duplicating_existing_services(): void
    {
        $this->seed(CoreDatabaseSeeder::class);

        $existingFbc = Service::query()->where('name', 'Full Blood Count (FBC)')->firstOrFail();
        $existingFbcPrice = $existingFbc->price;

        $this->seed(DiagnosticsDatabaseSeeder::class);
        $this->seed(DiagnosticsDatabaseSeeder::class);

        $this->assertDatabaseHas('service_categories', [
            'code' => 'PAT',
            'name' => 'Pathology',
        ]);

        $this->assertSame(1, Service::query()->where('name', 'Full Blood Count (FBC)')->count());
        $this->assertSame(1, Service::query()->where('name', 'Histopathology')->count());
        $this->assertSame(1, Service::query()->where('name', 'Pelvic Ultrasound')->count());

        $fbcService = Service::query()->where('name', 'Full Blood Count (FBC)')->firstOrFail();
        $this->assertSame((string) $existingFbc->id, (string) $fbcService->id);
        $this->assertSame($existingFbcPrice, $fbcService->price);

        $fbcProfile = DiagnosticServiceProfile::query()
            ->where('service_id', $fbcService->id)
            ->first();

        $this->assertNotNull($fbcProfile);
        $this->assertSame(DiagnosticDiscipline::LAB, $fbcProfile->discipline);
        $this->assertNotNull($fbcProfile->defaultTemplate);
        $this->assertSame(4, $fbcProfile->defaultTemplate->fields()->count());
        $this->assertTrue($fbcProfile->defaultTemplate->fields->pluck('label')->contains('Hemoglobin'));

        $histopathologyProfile = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Histopathology'))
            ->first();

        $this->assertNotNull($histopathologyProfile);
        $this->assertSame(DiagnosticDiscipline::PATHOLOGY, $histopathologyProfile->discipline);
        $this->assertNotNull($histopathologyProfile->defaultTemplate);

        $pelvicUltrasoundProfile = DiagnosticServiceProfile::query()
            ->whereHas('service', fn ($query) => $query->where('name', 'Pelvic Ultrasound'))
            ->first();

        $this->assertNotNull($pelvicUltrasoundProfile);
        $this->assertSame(DiagnosticDiscipline::RADIOLOGY, $pelvicUltrasoundProfile->discipline);
    }
}
