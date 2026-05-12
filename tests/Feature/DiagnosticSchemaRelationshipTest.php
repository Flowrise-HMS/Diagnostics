<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticMedia;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticReportVersion;
use Modules\Diagnostics\Models\DiagnosticSpecimen;
use Modules\Diagnostics\Models\DiagnosticStudy;
use Tests\TestCase;

class DiagnosticSchemaRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('module:migrate', ['module' => 'Clinical', '--force' => true]);
        $this->artisan('module:migrate', ['module' => 'Diagnostics', '--force' => true]);
    }

    public function test_request_item_can_only_have_one_diagnostic_fulfillment(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);

        DiagnosticFulfillment::factory()->create([
            'request_item_id' => $fulfillment->request_item_id,
            'branch_id' => $fulfillment->branch_id,
        ]);
    }

    public function test_observation_can_belong_to_fulfillment_and_optional_specimen(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();
        $specimen = DiagnosticSpecimen::create([
            'fulfillment_id' => $fulfillment->id,
            'specimen_type' => 'blood',
            'status' => 'collected',
        ]);

        $observation = DiagnosticObservation::create([
            'fulfillment_id' => $fulfillment->id,
            'specimen_id' => $specimen->id,
            'code' => '718-7',
            'status' => 'registered',
        ]);

        $this->assertTrue($observation->fulfillment->is($fulfillment));
        $this->assertTrue($observation->specimen->is($specimen));
    }

    public function test_report_version_can_link_multiple_observations_through_pivot(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();
        $report = DiagnosticReportVersion::create([
            'fulfillment_id' => $fulfillment->id,
            'version' => 1,
            'status' => 'preliminary',
        ]);
        $first = DiagnosticObservation::create([
            'fulfillment_id' => $fulfillment->id,
            'code' => '718-7',
            'status' => 'registered',
        ]);
        $second = DiagnosticObservation::create([
            'fulfillment_id' => $fulfillment->id,
            'code' => '6690-2',
            'status' => 'registered',
        ]);

        $report->observations()->attach($first->id, ['sort_order' => 1]);
        $report->observations()->attach($second->id, ['sort_order' => 2]);

        $this->assertCount(2, $report->observations);
    }

    public function test_study_can_have_media_entries(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create(['discipline' => 'radiology']);
        $study = DiagnosticStudy::create([
            'fulfillment_id' => $fulfillment->id,
            'status' => 'registered',
        ]);

        $media = DiagnosticMedia::create([
            'study_id' => $study->id,
            'file_type' => 'image',
            'file_name' => 'chest-xray.png',
            'file_path' => 'diagnostics/media/chest-xray.png',
        ]);

        $this->assertTrue($media->study->is($study));
        $this->assertCount(1, $study->media);
    }
}
