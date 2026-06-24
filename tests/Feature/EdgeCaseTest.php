<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrateModules(['Core', 'Patient', 'Clinical', 'Diagnostics']);
    }

    public function test_fulfillment_status_values(): void
    {
        $values = FulfillmentStatus::values();
        $this->assertContains('pending', $values);
        $this->assertContains('scheduled', $values);
        $this->assertContains('collected', $values);
        $this->assertContains('in_progress', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('cancelled', $values);
        $this->assertCount(6, $values);
    }

    public function test_fulfillment_status_labels(): void
    {
        $this->assertSame('Pending', FulfillmentStatus::PENDING->getLabel());
        $this->assertSame('Completed', FulfillmentStatus::COMPLETED->getLabel());
        $this->assertSame('Cancelled', FulfillmentStatus::CANCELLED->getLabel());
    }

    public function test_fulfillment_status_colors(): void
    {
        $this->assertSame('warning', FulfillmentStatus::PENDING->getColor());
        $this->assertSame('success', FulfillmentStatus::COMPLETED->getColor());
        $this->assertSame('gray', FulfillmentStatus::CANCELLED->getColor());
    }

    public function test_each_fulfillment_status_has_description(): void
    {
        foreach (FulfillmentStatus::cases() as $case) {
            $this->assertNotEmpty($case->getDescription());
        }
    }

    public function test_diagnostic_discipline_enum_values(): void
    {
        $values = DiagnosticDiscipline::values();
        $this->assertContains('lab', $values);
        $this->assertContains('radiology', $values);
        $this->assertContains('pathology', $values);
    }

    public function test_fulfillment_factory_casts_discipline_and_status_enums(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::LAB,
            'status' => FulfillmentStatus::PENDING,
        ]);

        $this->assertInstanceOf(DiagnosticDiscipline::class, $fulfillment->discipline);
        $this->assertInstanceOf(FulfillmentStatus::class, $fulfillment->status);
        $this->assertSame(DiagnosticDiscipline::LAB, $fulfillment->discipline);
        $this->assertSame(FulfillmentStatus::PENDING, $fulfillment->status);
    }
}
