<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Tests\TestCase;

class DiagnosticsModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('module:migrate', ['module' => 'Core', '--force' => true]);
        $this->artisan('module:migrate', ['module' => 'Patient', '--force' => true]);
        $this->artisan('module:migrate', ['module' => 'Diagnostics', '--force' => true]);
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
}
