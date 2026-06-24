<?php

namespace Modules\Diagnostics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Enums\ObservationStatus;
use Modules\Diagnostics\Enums\ReportVersionStatus;
use Modules\Diagnostics\Enums\SpecimenStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticFulfillmentAllocation;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticObservationComponent;
use Modules\Diagnostics\Models\DiagnosticPanel;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticReportSignature;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticSpecimen;
use Modules\Diagnostics\Models\DiagnosticSpecimenContainer;
use Modules\Diagnostics\Models\DiagnosticSpecimenProcessingEvent;
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
        $this->assertInstanceOf(FulfillmentStatus::class, $fulfillment->status);
        $this->assertInstanceOf(DiagnosticDiscipline::class, $fulfillment->discipline);
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
        $this->assertNotNull($signature->signature_type);
    }

    public function test_new_panel_models_factory_and_relationships(): void
    {
        $profile = DiagnosticServiceProfile::factory()->create();
        $panel = DiagnosticPanel::factory()->create(['profile_id' => $profile->id]);
        $childProfile = DiagnosticServiceProfile::factory()->create();
        $item = DiagnosticPanelItem::factory()->create([
            'panel_id' => $panel->id,
            'child_profile_id' => $childProfile->id,
        ]);
        $range = DiagnosticReferenceRange::factory()->create(['profile_id' => $profile->id]);

        $this->assertTrue($panel->profile->is($profile));
        $this->assertTrue($profile->panel->is($panel));
        $this->assertTrue($item->panel->is($panel));
        $this->assertTrue($item->childProfile->is($childProfile));
        $this->assertCount(1, $panel->items);
        $this->assertTrue($range->profile->is($profile));
        $this->assertCount(1, $profile->referenceRanges);
    }

    public function test_observation_hierarchy_and_components(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();
        $parent = DiagnosticObservation::factory()->create([
            'fulfillment_id' => $fulfillment->id,
            'status' => ObservationStatus::REGISTERED,
        ]);
        $child = DiagnosticObservation::factory()->create([
            'fulfillment_id' => $fulfillment->id,
            'parent_observation_id' => $parent->id,
        ]);
        $component = DiagnosticObservationComponent::factory()->create([
            'observation_id' => $parent->id,
        ]);

        $this->assertTrue($parent->childObservations->first()->is($child));
        $this->assertTrue($child->parentObservation->is($parent));
        $this->assertTrue($component->observation->is($parent));
        $this->assertCount(1, $parent->components);
    }

    public function test_specimen_containers_and_processing_events(): void
    {
        $specimen = DiagnosticSpecimen::factory()->create();
        $container = DiagnosticSpecimenContainer::factory()->create(['specimen_id' => $specimen->id]);
        $event = DiagnosticSpecimenProcessingEvent::factory()->create(['specimen_id' => $specimen->id]);

        $this->assertInstanceOf(SpecimenStatus::class, $specimen->status);
        $this->assertTrue($container->specimen->is($specimen));
        $this->assertTrue($event->specimen->is($specimen));
        $this->assertCount(1, $specimen->containers);
        $this->assertCount(1, $specimen->processingEvents);
    }

    public function test_fulfillment_allocations_relationship(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create();
        $allocation = DiagnosticFulfillmentAllocation::factory()->create([
            'fulfillment_id' => $fulfillment->id,
        ]);

        $this->assertTrue($allocation->fulfillment->is($fulfillment));
        $this->assertCount(1, $fulfillment->allocations);
    }

    public function test_fulfillment_workflow_methods_preserved(): void
    {
        $fulfillment = DiagnosticFulfillment::factory()->create([
            'status' => FulfillmentStatus::PENDING,
        ]);

        $fulfillment->schedule();
        $this->assertSame(FulfillmentStatus::SCHEDULED, $fulfillment->fresh()->status);

        $specimen = $fulfillment->collectSpecimen('blood');
        $this->assertSame(SpecimenStatus::COLLECTED, $specimen->status);
        $this->assertSame(FulfillmentStatus::COLLECTED, $fulfillment->fresh()->status);

        $fulfillment->startProcessing();
        $this->assertSame(FulfillmentStatus::IN_PROGRESS, $fulfillment->fresh()->status);

        $report = $fulfillment->finalizeResult();
        $this->assertSame(FulfillmentStatus::COMPLETED, $fulfillment->fresh()->status);
        $this->assertSame(ReportVersionStatus::FINAL, $report->status);

        $amended = $fulfillment->fresh()->amendReport();
        $this->assertSame(ReportVersionStatus::AMENDED, $amended->status);
    }

    public function test_filament_record_titles_are_strings_not_enums(): void
    {
        $profile = DiagnosticServiceProfile::factory()->create([
            'discipline' => DiagnosticDiscipline::LAB,
        ]);

        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::LAB,
            'accession_number' => 'LAB01-20260624-0001',
        ]);

        $this->assertIsString($profile->title);
        $this->assertStringContainsString('Lab', $profile->title);
        $this->assertIsString($fulfillment->title);
        $this->assertSame('LAB01-20260624-0001', $fulfillment->title);
    }
}
