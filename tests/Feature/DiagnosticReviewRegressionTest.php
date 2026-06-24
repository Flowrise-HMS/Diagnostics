<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\RequestPriority;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Clinical\Models\Task;
use Modules\Core\Enums\NavigationGroup;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticSpecimen;
use Modules\Diagnostics\Models\DiagnosticStudy;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DiagnosticReviewRegressionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules();
    }

    public function test_diagnostic_request_item_creation_and_cancellation_are_bridged_to_fulfillment_records(): void
    {
        $service = Service::factory()->create();

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $requestItem = RequestItem::factory()
            ->forService($service)
            ->create();

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $requestItem->id)
            ->first();

        $this->assertNotNull($fulfillment);
        $this->assertSame('lab', $fulfillment->discipline->value);
        $this->assertSame('pending', $fulfillment->status->value);

        $requestItem->cancel();

        $this->assertSame('cancelled', $fulfillment->fresh()->status->value);
    }

    public function test_bridge_generates_branch_scoped_accession_number_and_copies_request_context(): void
    {
        $branch = Branch::factory()->create(['code' => 'LAB01']);
        $service = Service::factory()->create();

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'default_specimen_type' => 'blood',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::factory()
            ->create([
                'branch_id' => $branch->id,
                'priority' => RequestPriority::URGENT,
                'notes' => 'Suspected anemia workup',
            ]);

        $requestItem = RequestItem::factory()
            ->forRequest($serviceRequest)
            ->forService($service)
            ->create();

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $requestItem->id)
            ->first();

        $this->assertNotNull($fulfillment);
        $this->assertMatchesRegularExpression('/^LAB01-\d{8}-\d{4}$/', (string) $fulfillment->accession_number);
        $this->assertSame('urgent', $fulfillment->priority);
        $this->assertSame('Suspected anemia workup', $fulfillment->clinical_indication);
        $this->assertSame(0, Task::query()->where('request_item_id', $requestItem->id)->count());
    }

    public function test_bridge_prefers_metadata_clinical_indication_over_request_notes(): void
    {
        $service = Service::factory()->create();

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $serviceRequest = ServiceRequest::factory()->create([
            'notes' => 'General order notes',
            'metadata' => ['clinical_indication' => 'Rule out infection'],
        ]);

        $requestItem = RequestItem::factory()
            ->forRequest($serviceRequest)
            ->forService($service)
            ->create();

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $requestItem->id)
            ->first();

        $this->assertNotNull($fulfillment);
        $this->assertSame('Rule out infection', $fulfillment->clinical_indication);
    }

    public function test_bridge_creates_radiology_study_stub_from_service_profile(): void
    {
        $service = Service::factory()->create();

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'radiology',
            'modality' => 'CT',
            'metadata' => ['body_site' => 'chest'],
            'is_active' => true,
        ]);

        $requestItem = RequestItem::factory()
            ->forService($service)
            ->create();

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $requestItem->id)
            ->first();

        $this->assertNotNull($fulfillment);

        $study = DiagnosticStudy::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->first();

        $this->assertNotNull($study);
        $this->assertSame('CT', $study->modality);
        $this->assertSame('chest', $study->body_site);
        $this->assertSame($fulfillment->accession_number, $study->accession_number);
        $this->assertSame('registered', $study->status->value);
        $this->assertSame(0, Task::query()->where('request_item_id', $requestItem->id)->count());
    }

    public function test_bridge_creates_default_specimen_for_lab_and_pathology_profiles(): void
    {
        $labService = Service::factory()->create();
        $pathologyService = Service::factory()->create();

        DiagnosticServiceProfile::create([
            'service_id' => $labService->id,
            'discipline' => 'lab',
            'default_specimen_type' => 'serum',
            'is_active' => true,
        ]);

        DiagnosticServiceProfile::create([
            'service_id' => $pathologyService->id,
            'discipline' => 'pathology',
            'default_specimen_type' => 'tissue',
            'is_active' => true,
        ]);

        $labItem = RequestItem::factory()->forService($labService)->create();
        $pathologyItem = RequestItem::factory()->forService($pathologyService)->create();

        $labFulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $labItem->id)
            ->first();
        $pathologyFulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $pathologyItem->id)
            ->first();

        $this->assertNotNull($labFulfillment);
        $this->assertNotNull($pathologyFulfillment);

        $labSpecimen = DiagnosticSpecimen::query()
            ->where('fulfillment_id', $labFulfillment->id)
            ->first();
        $pathologySpecimen = DiagnosticSpecimen::query()
            ->where('fulfillment_id', $pathologyFulfillment->id)
            ->first();

        $this->assertNotNull($labSpecimen);
        $this->assertSame('serum', $labSpecimen->specimen_type);
        $this->assertNotNull($pathologySpecimen);
        $this->assertSame('tissue', $pathologySpecimen->specimen_type);
    }

    public function test_diagnostics_filament_resources_exist_for_operations_and_admin_configuration(): void
    {
        $fulfillmentResource = 'Modules\\Diagnostics\\Filament\\Clusters\\Diagnostics\\Resources\\DiagnosticFulfillments\\DiagnosticFulfillmentResource';
        $profileResource = 'Modules\\Diagnostics\\Filament\\Clusters\\Diagnostics\\Resources\\DiagnosticServiceProfiles\\DiagnosticServiceProfileResource';
        $templateResource = 'Modules\\Diagnostics\\Filament\\Clusters\\Diagnostics\\Resources\\DiagnosticResultTemplates\\DiagnosticResultTemplateResource';
        $policyClass = 'Modules\\Diagnostics\\Policies\\DiagnosticFulfillmentPolicy';

        $this->assertTrue(class_exists($fulfillmentResource));
        $this->assertTrue(class_exists($profileResource));
        $this->assertTrue(class_exists($templateResource));
        $this->assertTrue(class_exists($policyClass));

        $this->assertSame('danger', NavigationGroup::DIAGNOSTICS->getColor());

        $fulfillmentPages = $fulfillmentResource::getPages();
        $this->assertArrayHasKey('index', $fulfillmentPages);
        $this->assertArrayHasKey('view', $fulfillmentPages);
        $this->assertArrayHasKey('edit', $fulfillmentPages);
        $this->assertArrayNotHasKey('create', $fulfillmentPages);

        $relations = $fulfillmentResource::getRelations();
        $this->assertContains(
            'Modules\\Diagnostics\\Filament\\Clusters\\Diagnostics\\Resources\\DiagnosticFulfillments\\RelationManagers\\DiagnosticSpecimensRelationManager',
            $relations
        );
        $this->assertContains(
            'Modules\\Diagnostics\\Filament\\Clusters\\Diagnostics\\Resources\\DiagnosticFulfillments\\RelationManagers\\DiagnosticObservationsRelationManager',
            $relations
        );

        $profilePages = $profileResource::getPages();
        $this->assertArrayHasKey('index', $profilePages);
        $this->assertArrayHasKey('create', $profilePages);
        $this->assertArrayHasKey('view', $profilePages);
        $this->assertArrayHasKey('edit', $profilePages);

        $templatePages = $templateResource::getPages();
        $this->assertArrayHasKey('index', $templatePages);
        $this->assertArrayHasKey('create', $templatePages);
        $this->assertArrayHasKey('view', $templatePages);
        $this->assertArrayHasKey('edit', $templatePages);
    }

    public function test_diagnostic_fulfillment_policy_enforces_custom_workflow_permissions(): void
    {
        $policyClass = 'Modules\\Diagnostics\\Policies\\DiagnosticFulfillmentPolicy';

        $user = User::factory()->create();
        $fulfillment = DiagnosticFulfillment::factory()->create();

        Permission::findOrCreate('collect_diagnostic_specimen', 'web');
        Permission::findOrCreate('upload_diagnostic_result_file', 'web');
        Permission::findOrCreate('finalize_diagnostic_result', 'web');
        Permission::findOrCreate('record_structured_diagnostic_observations', 'web');
        Permission::findOrCreate('verify_diagnostic_result', 'web');
        Permission::findOrCreate('sign_diagnostic_report', 'web');
        Permission::findOrCreate('amend_diagnostic_report', 'web');
        Permission::findOrCreate('print_diagnostic_lab_result', 'web');

        $user->givePermissionTo([
            'collect_diagnostic_specimen',
            'upload_diagnostic_result_file',
            'finalize_diagnostic_result',
            'record_structured_diagnostic_observations',
        ]);

        $policy = new $policyClass;

        $this->assertTrue($policy->collectSpecimen($user, $fulfillment));
        $this->assertTrue($policy->uploadResultFile($user, $fulfillment));
        $this->assertTrue($policy->finalizeResult($user, $fulfillment));
        $this->assertTrue($policy->recordStructuredResults($user, $fulfillment));
        $this->assertFalse($policy->verifyResult($user, $fulfillment));
        $this->assertFalse($policy->signReport($user, $fulfillment));
        $this->assertFalse($policy->amendReport($user, $fulfillment));
        $this->assertFalse($policy->printLabResult($user, $fulfillment));
    }
}
