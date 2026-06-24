<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\ObservationStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticPanel;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticReportVersion;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class DiagnosticObservationPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    protected DiagnosticResultService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);
        $this->service = app(DiagnosticResultService::class);
    }

    public function test_submit_persists_template_field_observations_with_reference_ranges(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['name' => 'Hemoglobin Test']);
        $patient = Patient::factory()->create([
            'branch_id' => Branch::factory()->create()->id,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(30),
        ]);
        $request = ServiceRequest::factory()->forPatient($patient)->create();
        $item = RequestItem::factory()->forRequest($request)->forService($service)->create();

        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $template = DiagnosticResultTemplate::create([
            'profile_id' => $profile->id,
            'name' => 'Hb Template',
            'is_default' => true,
            'is_active' => true,
        ]);

        DiagnosticResultTemplateField::create([
            'template_id' => $template->id,
            'field_key' => 'hemoglobin',
            'label' => 'Hemoglobin',
            'observation_code' => '718-7',
            'observation_name' => 'Hemoglobin',
            'value_type' => 'numeric',
            'default_units' => 'g/dL',
            'reference_range_low' => 12.0,
            'reference_range_high' => 16.0,
            'sort_order' => 1,
        ]);

        $this->service->submit($item, [
            'field_hemoglobin' => '17.5',
            'notes' => 'Slightly elevated',
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $observation = DiagnosticObservation::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->where('code', '718-7')
            ->first();

        $this->assertNotNull($observation);
        $this->assertSame('17.500000', $observation->value_numeric);
        $this->assertSame('g/dL', $observation->units);
        $this->assertSame('12.000000', $observation->reference_range_min);
        $this->assertSame('16.000000', $observation->reference_range_max);
        $this->assertSame(AbnormalFlag::HIGH, $observation->abnormal_flag);
        $this->assertSame(ObservationStatus::FINAL, $observation->status);
        $this->assertSame('Above reference range', $observation->interpretation);

        $reportVersion = DiagnosticReportVersion::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->firstOrFail();

        $this->assertTrue($reportVersion->observations->contains($observation));
    }

    public function test_submit_uses_population_reference_ranges_when_available(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $patient = Patient::factory()->create([
            'branch_id' => Branch::factory()->create()->id,
            'gender' => 'male',
            'date_of_birth' => now()->subYears(25),
        ]);
        $request = ServiceRequest::factory()->forPatient($patient)->create();
        $item = RequestItem::factory()->forRequest($request)->forService($service)->create();

        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        DiagnosticReferenceRange::create([
            'profile_id' => $profile->id,
            'gender' => 'male',
            'min_value' => 13.0,
            'max_value' => 17.0,
            'units' => 'g/dL',
            'critical_low' => 7.0,
            'critical_high' => 20.0,
        ]);

        $template = DiagnosticResultTemplate::create([
            'profile_id' => $profile->id,
            'name' => 'Default',
            'is_default' => true,
            'is_active' => true,
        ]);

        DiagnosticResultTemplateField::create([
            'template_id' => $template->id,
            'field_key' => 'hemoglobin',
            'label' => 'Hemoglobin',
            'value_type' => 'numeric',
            'reference_range_low' => 10.0,
            'reference_range_high' => 14.0,
            'sort_order' => 1,
        ]);

        $this->service->submit($item, [
            'field_hemoglobin' => '6.5',
        ], $user);

        $observation = DiagnosticObservation::query()
            ->where('code', 'hemoglobin')
            ->firstOrFail();

        $this->assertSame('13.000000', $observation->reference_range_min);
        $this->assertSame('17.000000', $observation->reference_range_max);
        $this->assertSame(AbnormalFlag::CRITICALLY_LOW, $observation->abnormal_flag);
    }

    public function test_panel_profile_seeds_multiple_observation_rows(): void
    {
        $user = User::factory()->create();
        $panelService = Service::factory()->create(['name' => 'Full Blood Count']);
        $hbService = Service::factory()->create(['name' => 'Hemoglobin']);
        $wbcService = Service::factory()->create(['name' => 'WBC']);
        $pltService = Service::factory()->create(['name' => 'Platelets']);

        $item = RequestItem::factory()->forService($panelService)->create();

        $panelProfile = DiagnosticServiceProfile::create([
            'service_id' => $panelService->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $hbProfile = DiagnosticServiceProfile::create([
            'service_id' => $hbService->id,
            'discipline' => 'lab',
            'loinc_code' => 'hemoglobin',
            'loinc_display' => 'Hemoglobin',
            'is_active' => true,
        ]);

        $wbcProfile = DiagnosticServiceProfile::create([
            'service_id' => $wbcService->id,
            'discipline' => 'lab',
            'loinc_code' => 'wbc',
            'loinc_display' => 'White Blood Cells',
            'is_active' => true,
        ]);

        $pltProfile = DiagnosticServiceProfile::create([
            'service_id' => $pltService->id,
            'discipline' => 'lab',
            'loinc_code' => 'platelets',
            'loinc_display' => 'Platelets',
            'is_active' => true,
        ]);

        $panel = DiagnosticPanel::create(['profile_id' => $panelProfile->id]);

        DiagnosticPanelItem::create([
            'panel_id' => $panel->id,
            'child_profile_id' => $hbProfile->id,
            'sequence' => 1,
            'is_required' => true,
        ]);

        DiagnosticPanelItem::create([
            'panel_id' => $panel->id,
            'child_profile_id' => $wbcProfile->id,
            'sequence' => 2,
            'is_required' => true,
        ]);

        DiagnosticPanelItem::create([
            'panel_id' => $panel->id,
            'child_profile_id' => $pltProfile->id,
            'sequence' => 3,
            'is_required' => true,
        ]);

        $this->service->submit($item, [
            'results' => [
                ['key' => 'hemoglobin', 'value' => '14.2'],
                ['key' => 'wbc', 'value' => '6.1'],
                ['key' => 'platelets', 'value' => '250'],
            ],
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $observations = DiagnosticObservation::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(3, $observations);
        $this->assertSame('hemoglobin', $observations[0]->code);
        $this->assertSame('14.200000', $observations[0]->value_numeric);
        $this->assertSame('wbc', $observations[1]->code);
        $this->assertSame('platelets', $observations[2]->code);

        $reportVersion = DiagnosticReportVersion::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->firstOrFail();

        $this->assertCount(3, $reportVersion->observations);
    }

    public function test_repeater_submit_persists_observations_linked_to_report_version(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $item = RequestItem::factory()->forService($service)->create();

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $this->service->submit($item, [
            'results' => [
                ['key' => 'hemoglobin', 'value' => '13.5 g/dL'],
            ],
            'notes' => 'Within normal limits',
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $observation = DiagnosticObservation::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->where('code', 'hemoglobin')
            ->firstOrFail();

        $this->assertSame('Hemoglobin', $observation->display);
        $this->assertSame('13.5 g/dL', $observation->value_text);

        $reportVersion = DiagnosticReportVersion::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->firstOrFail();

        $this->assertTrue($reportVersion->observations->contains($observation));
    }
}
