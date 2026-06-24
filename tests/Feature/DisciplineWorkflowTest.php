<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Models\RequestItem;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\AllocationStatus;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Enums\ReportVersionStatus;
use Modules\Diagnostics\Enums\SpecimenStatus;
use Modules\Diagnostics\Enums\StudyStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticFulfillmentAllocation;
use Modules\Diagnostics\Models\DiagnosticMedia;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticReportSignature;
use Modules\Diagnostics\Models\DiagnosticReportVersion;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticSpecimenContainer;
use Modules\Diagnostics\Models\DiagnosticStudy;
use Tests\TestCase;

class DisciplineWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected DiagnosticResultService $resultService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);
        $this->resultService = app(DiagnosticResultService::class);
    }

    public function test_lab_collect_specimen_creates_full_specimen_record(): void
    {
        $collector = User::factory()->create();
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::LAB,
            'status' => FulfillmentStatus::PENDING,
            'accession_number' => 'LAB01-20260624-0001',
        ]);

        $specimen = $fulfillment->collectSpecimen('blood', $collector);

        $this->assertSame(SpecimenStatus::COLLECTED, $specimen->status);
        $this->assertSame('blood', $specimen->specimen_type);
        $this->assertSame('LAB01-20260624-0001', $specimen->accession_number);
        $this->assertNotNull($specimen->collected_at);
        $this->assertSame($collector->id, $specimen->collected_by);
        $this->assertSame(FulfillmentStatus::COLLECTED, $fulfillment->fresh()->status);
        $this->assertNotNull($fulfillment->fresh()->collection_date);
    }

    public function test_lab_start_processing_advances_fulfillment_status(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::LAB,
            'status' => FulfillmentStatus::COLLECTED,
        ]);

        $fulfillment->startProcessing();

        $this->assertSame(FulfillmentStatus::IN_PROGRESS, $fulfillment->fresh()->status);
    }

    public function test_lab_finalize_result_evaluates_reference_ranges_on_submit(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['name' => 'Glucose']);
        $item = RequestItem::factory()->forService($service)->create();

        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $template = DiagnosticResultTemplate::create([
            'profile_id' => $profile->id,
            'name' => 'Glucose',
            'is_default' => true,
            'is_active' => true,
        ]);

        DiagnosticResultTemplateField::create([
            'template_id' => $template->id,
            'field_key' => 'glucose',
            'label' => 'Glucose',
            'value_type' => 'numeric',
            'default_units' => 'mmol/L',
            'reference_range_low' => 3.9,
            'reference_range_high' => 6.1,
            'sort_order' => 1,
        ]);

        $this->resultService->submit($item, ['field_glucose' => '8.2'], $user);

        $observation = DiagnosticObservation::query()->where('code', 'glucose')->firstOrFail();

        $this->assertSame(AbnormalFlag::HIGH, $observation->abnormal_flag);
        $this->assertSame('Above reference range', $observation->interpretation);
    }

    public function test_lab_verify_result_sets_verified_metadata(): void
    {
        $verifier = User::factory()->create();
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::LAB,
            'status' => FulfillmentStatus::IN_PROGRESS,
        ]);

        $fulfillment->finalizeResult();
        $report = $fulfillment->verifyResult($verifier);

        $this->assertNotNull($report);
        $this->assertSame(ReportVersionStatus::FINAL, $report->status);
        $this->assertSame($verifier->id, $report->verified_by);
        $this->assertNotNull($report->verified_at);
    }

    public function test_lab_auto_verifies_when_profile_eligible_and_all_values_normal(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $item = RequestItem::factory()->forService($service)->create();

        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'auto_verify_eligible' => true,
            'is_active' => true,
        ]);

        $template = DiagnosticResultTemplate::create([
            'profile_id' => $profile->id,
            'name' => 'Hb',
            'is_default' => true,
            'is_active' => true,
        ]);

        DiagnosticResultTemplateField::create([
            'template_id' => $template->id,
            'field_key' => 'hemoglobin',
            'label' => 'Hemoglobin',
            'value_type' => 'numeric',
            'reference_range_low' => 12.0,
            'reference_range_high' => 16.0,
            'sort_order' => 1,
        ]);

        $this->resultService->submit($item, ['field_hemoglobin' => '14.0'], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $report = $fulfillment->latestReportVersion;

        $this->assertNotNull($report);
        $this->assertSame($user->id, $report->verified_by);
        $this->assertNotNull($report->verified_at);
        $this->assertSame(AbnormalFlag::NORMAL, DiagnosticObservation::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->first()
            ->abnormal_flag);
    }

    public function test_lab_does_not_auto_verify_when_values_are_abnormal(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $item = RequestItem::factory()->forService($service)->create();

        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'auto_verify_eligible' => true,
            'is_active' => true,
        ]);

        $template = DiagnosticResultTemplate::create([
            'profile_id' => $profile->id,
            'name' => 'Hb',
            'is_default' => true,
            'is_active' => true,
        ]);

        DiagnosticResultTemplateField::create([
            'template_id' => $template->id,
            'field_key' => 'hemoglobin',
            'label' => 'Hemoglobin',
            'value_type' => 'numeric',
            'reference_range_low' => 12.0,
            'reference_range_high' => 16.0,
            'sort_order' => 1,
        ]);

        $this->resultService->submit($item, ['field_hemoglobin' => '18.0'], $user);

        $report = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail()
            ->latestReportVersion;

        $this->assertNull($report->verified_by);
        $this->assertNull($report->verified_at);
    }

    public function test_radiology_schedule_creates_allocation_stub(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::RADIOLOGY,
            'status' => FulfillmentStatus::PENDING,
        ]);

        $scheduledAt = now()->addDay()->startOfSecond();
        $fulfillment->schedule($scheduledAt, 'ct_scanner', 'scanner-1');

        $fulfillment->refresh();

        $this->assertSame(FulfillmentStatus::SCHEDULED, $fulfillment->status);
        $this->assertSame(
            $scheduledAt->format('Y-m-d H:i:s'),
            $fulfillment->scheduled_at->format('Y-m-d H:i:s'),
        );

        $allocation = DiagnosticFulfillmentAllocation::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->firstOrFail();

        $this->assertSame('ct_scanner', $allocation->resource_type);
        $this->assertSame('scanner-1', $allocation->resource_id);
        $this->assertSame(AllocationStatus::SCHEDULED, $allocation->status);
        $this->assertSame(
            $scheduledAt->format('Y-m-d H:i:s'),
            $allocation->scheduled_start->format('Y-m-d H:i:s'),
        );
    }

    public function test_radiology_study_registration_inherits_profile_modality_and_body_site(): void
    {
        $service = Service::factory()->create();

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'radiology',
            'modality' => 'MRI',
            'metadata' => ['body_site' => 'brain'],
            'is_active' => true,
        ]);

        $requestItem = RequestItem::factory()->forService($service)->create();

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $requestItem->id)
            ->firstOrFail();

        $study = DiagnosticStudy::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->firstOrFail();

        $this->assertSame('MRI', $study->modality);
        $this->assertSame('brain', $study->body_site);
        $this->assertSame(StudyStatus::REGISTERED, $study->status);
        $this->assertSame($fulfillment->accession_number, $study->accession_number);
    }

    public function test_radiology_media_upload_attaches_to_study_and_fulfillment(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::RADIOLOGY,
        ]);

        $study = $fulfillment->study()->create([
            'modality' => 'XR',
            'status' => StudyStatus::REGISTERED,
        ]);

        $media = $study->media()->create([
            'file_type' => 'pdf',
            'file_name' => 'radiology-report.pdf',
            'file_path' => 'diagnostics/media/radiology-report.pdf',
            'mime_type' => 'application/pdf',
            'is_key_image' => true,
        ]);

        $this->assertInstanceOf(DiagnosticMedia::class, $media);
        $this->assertCount(1, $fulfillment->fresh()->media);
        $this->assertTrue($fulfillment->media->first()->is_key_image);
    }

    public function test_radiology_sign_report_records_signature_on_latest_version(): void
    {
        $radiologist = User::factory()->create();
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::RADIOLOGY,
        ]);

        $fulfillment->finalizeResult();

        $signature = $fulfillment->signReport($radiologist, 'radiologist', 'Read and approved');

        $this->assertInstanceOf(DiagnosticReportSignature::class, $signature);
        $this->assertSame($radiologist->id, $signature->signed_by);
    }

    public function test_pathology_specimen_and_container_workflow(): void
    {
        $collector = User::factory()->create();
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::PATHOLOGY,
            'status' => FulfillmentStatus::PENDING,
        ]);

        $specimen = $fulfillment->collectSpecimen('tissue', $collector, null, [
            'body_site' => 'left breast',
            'collection_method' => 'core biopsy',
        ]);

        $container = DiagnosticSpecimenContainer::create([
            'specimen_id' => $specimen->id,
            'container_type' => 'formalin jar',
            'identifier' => 'A1',
        ]);

        $this->assertSame('tissue', $specimen->specimen_type);
        $this->assertSame('left breast', $specimen->body_site);
        $this->assertSame($collector->id, $specimen->collected_by);
        $this->assertTrue($container->specimen->is($specimen));
        $this->assertCount(1, $specimen->fresh()->containers);
    }

    public function test_pathology_narrative_report_fields_persist_conclusion_and_text_observations(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['name' => 'Biopsy']);
        $item = RequestItem::factory()->forService($service)->create();

        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'pathology',
            'is_active' => true,
        ]);

        $template = DiagnosticResultTemplate::create([
            'profile_id' => $profile->id,
            'name' => 'Pathology Report',
            'is_default' => true,
            'is_active' => true,
        ]);

        DiagnosticResultTemplateField::create([
            'template_id' => $template->id,
            'field_key' => 'gross_description',
            'label' => 'Gross Description',
            'observation_code' => 'gross',
            'value_type' => 'text',
            'sort_order' => 1,
        ]);

        DiagnosticResultTemplateField::create([
            'template_id' => $template->id,
            'field_key' => 'microscopic_description',
            'label' => 'Microscopic Description',
            'observation_code' => 'microscopic',
            'value_type' => 'text',
            'sort_order' => 2,
        ]);

        $this->resultService->submit($item, [
            'field_gross_description' => 'Tan tissue fragment measuring 1.2 cm.',
            'field_microscopic_description' => 'Sections show benign ductal tissue.',
            'diagnosis' => 'Benign breast tissue.',
            'report_title' => 'Core Biopsy Report',
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $report = DiagnosticReportVersion::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->firstOrFail();

        $this->assertSame('Core Biopsy Report', $report->title);
        $this->assertStringContainsString('Gross: Tan tissue fragment measuring 1.2 cm.', $report->conclusion);
        $this->assertStringContainsString('Microscopic: Sections show benign ductal tissue.', $report->conclusion);
        $this->assertStringContainsString('Diagnosis: Benign breast tissue.', $report->conclusion);

        $grossObservation = DiagnosticObservation::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->where('code', 'gross')
            ->firstOrFail();

        $this->assertSame('Tan tissue fragment measuring 1.2 cm.', $grossObservation->value_text);
    }

    public function test_pathology_amend_report_creates_amended_version(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::PATHOLOGY,
            'status' => FulfillmentStatus::IN_PROGRESS,
        ]);

        $original = $fulfillment->finalizeResult(ReportVersionStatus::FINAL->value, [
            'conclusion' => 'Initial diagnosis.',
        ]);

        $amended = $fulfillment->fresh()->amendReport();

        $this->assertSame(ReportVersionStatus::AMENDED, $amended->status);
        $this->assertSame(2, $amended->version);
        $this->assertNotSame($original->id, $amended->id);
        $this->assertCount(2, $fulfillment->fresh()->reportVersions);
    }
}
