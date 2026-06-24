<?php

namespace Modules\Diagnostics\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticReportVersion;
use Modules\Diagnostics\Models\DiagnosticResultFile;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Tests\TestCase;

class DiagnosticResultServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected DiagnosticResultService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);
        $this->service = app(DiagnosticResultService::class);
    }

    public function test_get_context_info_returns_order_metadata(): void
    {
        $service = Service::factory()->create(['name' => 'Full Blood Count']);
        $request = ServiceRequest::factory()->create();
        $item = RequestItem::factory()
            ->forRequest($request)
            ->forService($service)
            ->create();

        $context = $this->service->getContextInfo($item->fresh(['service.category', 'serviceRequest.orderedBy']));

        $this->assertSame('Full Blood Count', $context['service_name']);
        $this->assertNotSame('N/A', $context['ordered_at']);
    }

    public function test_submit_creates_task_and_finalizes_fulfillment(): void
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
                ['key' => 'hemoglobin', 'value' => '13.5'],
            ],
            'notes' => 'Within normal limits',
        ], $user);

        $item->refresh();

        $this->assertSame('completed', $item->status->value);
        $this->assertCount(1, $item->tasks);
        $this->assertSame('Within normal limits', $item->tasks->first()->notes);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->first();

        $this->assertNotNull($fulfillment);
        $this->assertSame('completed', $fulfillment->status->value);
    }

    public function test_submit_stores_reduced_summary_in_task_results(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $item = RequestItem::factory()->forService($service)->create();

        $profile = DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
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
            'reference_range_low' => 12.0,
            'reference_range_high' => 16.0,
            'sort_order' => 1,
        ]);

        $this->service->submit($item, [
            'field_hemoglobin' => '17.5',
        ], $user);

        $task = $item->fresh()->tasks->first();

        $this->assertArrayHasKey('hemoglobin', $task->results);
        $this->assertSame('17.500000', (string) $task->results['hemoglobin']['value']);
        $this->assertSame('high', $task->results['hemoglobin']['abnormal_flag']);
        $this->assertArrayNotHasKey('label', $task->results['hemoglobin']);
    }

    public function test_submit_links_uploaded_files_to_report_version(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $service = Service::factory()->create();
        $item = RequestItem::factory()->forService($service)->create();

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $uploadedFile = UploadedFile::fake()->create('result.pdf', 100, 'application/pdf');

        $this->service->submit($item, [
            'results' => [
                ['key' => 'result', 'value' => 'Negative'],
            ],
            'result_files' => [$uploadedFile],
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $reportVersion = DiagnosticReportVersion::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->firstOrFail();

        $file = DiagnosticResultFile::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->firstOrFail();

        $this->assertSame($reportVersion->id, $file->report_version_id);
        $this->assertSame('result.pdf', $file->file_name);
    }

    public function test_submit_creates_structured_observations(): void
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
                ['key' => 'hemoglobin', 'value' => '13.5'],
            ],
        ], $user);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->firstOrFail();

        $this->assertSame(1, DiagnosticObservation::query()
            ->where('fulfillment_id', $fulfillment->id)
            ->count());
    }
}
