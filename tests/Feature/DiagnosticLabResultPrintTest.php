<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticLabResultPrintService;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Patient\Models\Patient;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DiagnosticLabResultPrintTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);
    }

    public function test_patient_lab_result_print_page_renders_completed_results(): void
    {
        $user = $this->createUserWithViewPermission();
        $service = Service::factory()->create(['name' => 'Full Blood Count']);

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $patient = Patient::factory()->create([
            'branch_id' => Branch::factory()->create()->id,
        ]);
        $request = ServiceRequest::factory()->forPatient($patient)->create();
        $item = RequestItem::factory()->forRequest($request)->forService($service)->create();

        app(DiagnosticResultService::class)->submit($item, [
            'results' => [
                ['key' => 'hemoglobin', 'value' => '13.5 g/dL'],
            ],
            'notes' => 'Within normal limits',
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('diagnostics.fulfillments.lab-result.print', $fulfillment));

        $response->assertOk();
        $response->assertSee('Laboratory Result Report');
        $response->assertSee($patient->full_name);
        $response->assertSee('Full Blood Count');
        $response->assertSee('Hemoglobin');
        $response->assertSee('13.5 g/dL');
        $response->assertSee('Within normal limits');
    }

    public function test_guest_lab_result_print_page_renders_guest_identity(): void
    {
        $user = $this->createUserWithViewPermission();
        $service = Service::factory()->create(['name' => 'Malaria RDT']);

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $request = ServiceRequest::factory()->asGuest()->create([
            'guest_name' => 'Jane Walk-In',
            'guest_phone' => '+233201234567',
        ]);
        $item = RequestItem::factory()->forRequest($request)->forService($service)->create();

        app(DiagnosticResultService::class)->submit($item, [
            'results' => [
                ['key' => 'result', 'value' => 'Negative'],
            ],
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $response = $this->actingAs($user)
            ->get(route('diagnostics.fulfillments.lab-result.print', $fulfillment));

        $response->assertOk();
        $response->assertSee('Guest');
        $response->assertSee('Jane Walk-In');
        $response->assertSee('+233201234567');
        $response->assertSee('Malaria RDT');
        $response->assertSee('Negative');
    }

    public function test_print_is_unavailable_for_incomplete_lab_fulfillment(): void
    {
        $user = $this->createUserWithViewPermission();
        $service = Service::factory()->create();
        $item = RequestItem::factory()->forService($service)->create();

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $fulfillment = DiagnosticFulfillment::factory()
            ->forRequestItem($item, 'lab')
            ->create(['status' => 'pending']);

        $this->actingAs($user)
            ->get(route('diagnostics.fulfillments.lab-result.print', $fulfillment))
            ->assertNotFound();
    }

    public function test_print_service_rejects_non_lab_discipline(): void
    {
        $service = Service::factory()->create();
        $item = RequestItem::factory()->forService($service)->create();

        $fulfillment = DiagnosticFulfillment::query()->create([
            'request_item_id' => $item->id,
            'branch_id' => $item->serviceRequest->branch_id,
            'discipline' => 'radiology',
            'status' => 'completed',
        ]);

        $this->assertFalse(app(DiagnosticLabResultPrintService::class)->canPrint($fulfillment));
    }

    protected function createUserWithViewPermission(): User
    {
        $user = User::factory()->create();

        Permission::findOrCreate('print_diagnostic_lab_result', 'web');
        $user->givePermissionTo('print_diagnostic_lab_result');

        return $user;
    }
}
