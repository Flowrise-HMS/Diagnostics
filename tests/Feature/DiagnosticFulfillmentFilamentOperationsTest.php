<?php

namespace Modules\Diagnostics\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Filament\Actions\RecordStructuredResultsAction;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\DiagnosticFulfillmentResource;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ViewDiagnosticFulfillment;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticAllocationsRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticMediaRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticObservationsRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticSpecimensRelationManager;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\RelationManagers\DiagnosticStudiesRelationManager;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticReportVersion;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DiagnosticFulfillmentFilamentOperationsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_fulfillment_resource_registers_phase_five_relation_managers(): void
    {
        $relations = DiagnosticFulfillmentResource::getRelations();

        $this->assertContains(DiagnosticSpecimensRelationManager::class, $relations);
        $this->assertContains(DiagnosticObservationsRelationManager::class, $relations);
        $this->assertContains(DiagnosticStudiesRelationManager::class, $relations);
        $this->assertContains(DiagnosticMediaRelationManager::class, $relations);
        $this->assertContains(DiagnosticAllocationsRelationManager::class, $relations);
    }

    public function test_fulfillment_record_title_is_a_string_and_not_the_discipline_enum(): void
    {
        $this->migrateModules();

        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::RADIOLOGY,
            'accession_number' => 'ACC-TITLE-001',
        ]);

        $title = DiagnosticFulfillmentResource::getRecordTitle($fulfillment);

        $this->assertIsString($title);
        $this->assertSame('ACC-TITLE-001', $title);
    }

    public function test_discipline_helpers_gate_specimen_and_scheduling_workflows(): void
    {
        $this->assertTrue(DiagnosticDiscipline::LAB->supportsSpecimenWorkflow());
        $this->assertTrue(DiagnosticDiscipline::PATHOLOGY->supportsSpecimenWorkflow());
        $this->assertFalse(DiagnosticDiscipline::RADIOLOGY->supportsSpecimenWorkflow());

        $this->assertTrue(DiagnosticDiscipline::RADIOLOGY->supportsSchedulingWorkflow());
        $this->assertFalse(DiagnosticDiscipline::LAB->supportsSchedulingWorkflow());
    }

    public function test_record_structured_results_action_class_exists(): void
    {
        $this->assertTrue(class_exists(RecordStructuredResultsAction::class));
        $this->assertSame('recordStructuredResults', RecordStructuredResultsAction::make()->getName());
    }

    public function test_record_structured_results_action_is_visible_on_table_row_for_authorized_user(): void
    {
        $this->migrateModules();

        $user = User::factory()->create();
        Permission::findOrCreate('record_structured_diagnostic_observations', 'web');
        $user->givePermissionTo('record_structured_diagnostic_observations');

        $fulfillment = DiagnosticFulfillment::factory()->create();

        $action = RecordStructuredResultsAction::make()->record($fulfillment);

        $this->actingAs($user);

        $this->assertTrue($action->isVisible());
    }

    public function test_record_structured_results_action_is_hidden_on_table_row_without_permission(): void
    {
        $this->migrateModules();

        $user = User::factory()->create();
        $fulfillment = DiagnosticFulfillment::factory()->create();

        $action = RecordStructuredResultsAction::make()->record($fulfillment);

        $this->actingAs($user);

        $this->assertFalse($action->isVisible());
    }

    public function test_record_structured_results_action_is_hidden_on_table_row_without_request_item(): void
    {
        $this->migrateModules();

        $user = User::factory()->create();
        Permission::findOrCreate('record_structured_diagnostic_observations', 'web');
        $user->givePermissionTo('record_structured_diagnostic_observations');

        $fulfillment = new DiagnosticFulfillment;

        $action = RecordStructuredResultsAction::make()->record($fulfillment);

        $this->actingAs($user);

        $this->assertFalse($action->isVisible());
    }

    public function test_record_structured_results_action_header_context_remains_visible(): void
    {
        $this->migrateModules();

        $user = User::factory()->create();
        Permission::findOrCreate('record_structured_diagnostic_observations', 'web');
        $user->givePermissionTo('record_structured_diagnostic_observations');

        $fulfillment = DiagnosticFulfillment::factory()->create();

        $action = RecordStructuredResultsAction::make(fn (): DiagnosticFulfillment => $fulfillment);

        $this->actingAs($user);

        $this->assertTrue($action->isVisible());
    }

    public function test_relation_managers_are_discipline_aware(): void
    {
        $this->migrateModules();

        DiagnosticSpecimensRelationManager::skipAuthorization();
        DiagnosticStudiesRelationManager::skipAuthorization();
        DiagnosticAllocationsRelationManager::skipAuthorization();
        DiagnosticMediaRelationManager::skipAuthorization();
        DiagnosticObservationsRelationManager::skipAuthorization();

        $labFulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::LAB,
        ]);
        $radiologyFulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::RADIOLOGY,
        ]);

        $pageClass = ViewDiagnosticFulfillment::class;

        $this->assertTrue(DiagnosticSpecimensRelationManager::canViewForRecord($labFulfillment, $pageClass));
        $this->assertFalse(DiagnosticSpecimensRelationManager::canViewForRecord($radiologyFulfillment, $pageClass));

        $this->assertTrue(DiagnosticStudiesRelationManager::canViewForRecord($radiologyFulfillment, $pageClass));
        $this->assertFalse(DiagnosticStudiesRelationManager::canViewForRecord($labFulfillment, $pageClass));

        $this->assertTrue(DiagnosticAllocationsRelationManager::canViewForRecord($radiologyFulfillment, $pageClass));
        $this->assertFalse(DiagnosticAllocationsRelationManager::canViewForRecord($labFulfillment, $pageClass));

        $this->assertTrue(DiagnosticMediaRelationManager::canViewForRecord($radiologyFulfillment, $pageClass));
        $this->assertFalse(DiagnosticMediaRelationManager::canViewForRecord($labFulfillment, $pageClass));

        $this->assertTrue(DiagnosticObservationsRelationManager::canViewForRecord($labFulfillment, $pageClass));
        $this->assertTrue(DiagnosticObservationsRelationManager::canViewForRecord($radiologyFulfillment, $pageClass));
    }

    public function test_fulfillment_media_relationship_traverses_study(): void
    {
        $this->migrateModules();

        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::RADIOLOGY,
        ]);

        $study = $fulfillment->study()->create([
            'modality' => 'CT',
            'status' => 'registered',
        ]);

        $study->media()->create([
            'file_type' => 'dicom',
            'file_name' => 'series-1.dcm',
            'file_path' => 'diagnostics/media/series-1.dcm',
        ]);

        $this->assertCount(1, $fulfillment->fresh()->media);
    }

    public function test_fulfillment_tracks_critical_report_versions(): void
    {
        $this->migrateModules();

        $fulfillment = DiagnosticFulfillment::factory()->create();

        DiagnosticReportVersion::factory()->create([
            'fulfillment_id' => $fulfillment->id,
            'version' => 1,
            'is_critical' => true,
        ]);

        $this->assertTrue($fulfillment->fresh()->hasCriticalReport());
    }

    public function test_schedule_sets_scheduled_at_timestamp(): void
    {
        $this->migrateModules();

        $fulfillment = DiagnosticFulfillment::factory()->create([
            'discipline' => DiagnosticDiscipline::RADIOLOGY,
            'status' => 'pending',
        ]);

        $scheduledAt = now()->addDay()->startOfSecond();
        $fulfillment->schedule($scheduledAt);

        $fulfillment->refresh();

        $this->assertSame('scheduled', $fulfillment->status->value);
        $this->assertNotNull($fulfillment->scheduled_at);
        $this->assertSame(
            $scheduledAt->format('Y-m-d H:i:s'),
            $fulfillment->scheduled_at->format('Y-m-d H:i:s'),
        );
    }
}
