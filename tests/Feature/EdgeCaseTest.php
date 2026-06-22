<?php

namespace Modules\Diagnostics\Tests\Feature;

use Modules\Diagnostics\Enums\FulfillmentStatus;
use Tests\TestCase;

class EdgeCaseTest extends TestCase
{
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
}
