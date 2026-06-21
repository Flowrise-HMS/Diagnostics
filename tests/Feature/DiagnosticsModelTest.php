<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticReportSignature;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Tests\TestCase;

class DiagnosticsModelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->migrateModules();
    }

    public function test_diagnostic_fulfillment_factory(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();
        $this->assertTrue($fulfillment->exists);
        $this->assertNotNull($fulfillment->id);
    }

    public function test_diagnostic_result_template_factory(): void
    {
        $template = DiagnosticResultTemplate::factory()->create();
        $this->assertTrue($template->exists);
        $this->assertNotNull($template->id);
    }

    public function test_diagnostic_report_signature_factory(): void
    {
        $signature = DiagnosticReportSignature::factory()->create();
        $this->assertTrue($signature->exists);
        $this->assertNotNull($signature->id);
    }
}
