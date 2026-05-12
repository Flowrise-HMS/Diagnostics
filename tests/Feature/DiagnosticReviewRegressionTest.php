<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Clinical\Models\RequestItem;
use Modules\Core\Enums\NavigationGroup;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DiagnosticReviewRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('module:migrate', ['module' => 'Clinical', '--force' => true]);
        $this->artisan('module:migrate', ['module' => 'Diagnostics', '--force' => true]);
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
        $this->assertSame('lab', $fulfillment->discipline);
        $this->assertSame('pending', $fulfillment->status->value);

        $requestItem->cancel();

        $this->assertSame('cancelled', $fulfillment->fresh()->status->value);
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
        Permission::findOrCreate('verify_diagnostic_result', 'web');
        Permission::findOrCreate('sign_diagnostic_report', 'web');
        Permission::findOrCreate('amend_diagnostic_report', 'web');

        $user->givePermissionTo([
            'collect_diagnostic_specimen',
            'upload_diagnostic_result_file',
            'finalize_diagnostic_result',
        ]);

        $policy = new $policyClass;

        $this->assertTrue($policy->collectSpecimen($user, $fulfillment));
        $this->assertTrue($policy->uploadResultFile($user, $fulfillment));
        $this->assertTrue($policy->finalizeResult($user, $fulfillment));
        $this->assertFalse($policy->verifyResult($user, $fulfillment));
        $this->assertFalse($policy->signReport($user, $fulfillment));
        $this->assertFalse($policy->amendReport($user, $fulfillment));
    }
}
