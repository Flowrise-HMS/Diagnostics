<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Enums\TaskStatus;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\Task;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticLabResultPrintService;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

class DiagnosticLegacyTaskResultsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);
    }

    public function test_legacy_task_results_remain_readable_after_observation_writer_refactor(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['name' => 'Legacy Panel']);
        $branch = Branch::factory()->create();
        $patient = Patient::factory()->create(['branch_id' => $branch->id]);

        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $item = RequestItem::factory()->forService($service)->create();

        Task::query()->create([
            'request_item_id' => $item->id,
            'status' => TaskStatus::COMPLETED,
            'performed_by' => $user->id,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'results' => [
                'hemoglobin' => [
                    'label' => 'Hemoglobin',
                    'value' => '12.1 g/dL',
                    'type' => 'numeric',
                ],
            ],
            'notes' => 'Legacy row',
        ]);

        $fulfillment = DiagnosticFulfillment::query()
            ->where('request_item_id', $item->id)
            ->first();

        if ($fulfillment === null) {
            $fulfillment = DiagnosticFulfillment::factory()
                ->forRequestItem($item, 'lab')
                ->create(['status' => 'completed', 'branch_id' => $branch->id]);
        } else {
            $fulfillment->update(['status' => 'completed']);
        }

        $fulfillment->finalizeResult('final');

        $printService = app(DiagnosticLabResultPrintService::class);

        $this->assertTrue($printService->canPrint($fulfillment->fresh()));
        $payload = $printService->build($fulfillment->fresh());
        $this->assertSame('12.1 g/dL', $payload['resultRows']->first()['value'] ?? null);
        $this->assertSame('Legacy row', $payload['notes']);
    }

    public function test_new_submissions_still_write_reduced_task_summary(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        DiagnosticServiceProfile::create([
            'service_id' => $service->id,
            'discipline' => 'lab',
            'is_active' => true,
        ]);

        $item = RequestItem::factory()->forService($service)->create();

        app(DiagnosticResultService::class)->submit($item, [
            'results' => [
                ['key' => 'glucose', 'value' => '5.2'],
            ],
        ], $user);

        $task = $item->fresh()->tasks->first();

        $this->assertIsArray($task->results);
        $this->assertArrayHasKey('glucose', $task->results);
    }
}
