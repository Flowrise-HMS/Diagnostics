<?php

namespace Modules\Diagnostics\Tests\Unit;

use Modules\Diagnostics\Enums\FulfillmentStatus;
use Tests\TestCase;

class FulfillmentStatusTest extends TestCase
{
    public function test_values_returns_all_cases(): void
    {
        $values = FulfillmentStatus::values();

        $this->assertIsArray($values);
        $this->assertCount(count(FulfillmentStatus::cases()), $values);
    }

    public function test_each_case_has_label(): void
    {
        foreach (FulfillmentStatus::cases() as $case) {
            $this->assertNotEmpty($case->getLabel());
        }
    }

    public function test_each_case_has_color(): void
    {
        foreach (FulfillmentStatus::cases() as $case) {
            $this->assertNotNull($case->getColor());
        }
    }

    public function test_each_case_has_description(): void
    {
        foreach (FulfillmentStatus::cases() as $case) {
            $this->assertNotEmpty($case->getDescription());
        }
    }
}
