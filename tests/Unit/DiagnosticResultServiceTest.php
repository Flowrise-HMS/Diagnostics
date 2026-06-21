<?php

namespace Modules\Diagnostics\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
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
}
