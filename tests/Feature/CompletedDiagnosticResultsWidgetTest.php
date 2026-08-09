<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Clinical\Enums\TaskStatus;
use Modules\Clinical\Filament\Clusters\Workspace\Pages\ClinicalWorkspace;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Models\Branch;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Enums\ObservationStatus;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ViewDiagnosticFulfillment;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Tables\DiagnosticFulfillmentsTable;
use Modules\Diagnostics\Filament\Widgets\CompletedDiagnosticResultsWidget;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticResultFile;
use Modules\Patient\Models\Patient;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Staff', 'Diagnostics']);

    $this->branch = Branch::factory()->default()->create();
    $this->patient = Patient::withoutEvents(
        fn (): Patient => Patient::factory()->create(['branch_id' => $this->branch->id])
    );
});

function diagnosticWidgetUser(Branch $branch, array $permissions = []): User
{
    $user = User::factory()->create(['branch_id' => $branch->id]);
    Role::findOrCreate('lab_technician', 'web');
    $user->assignRole('lab_technician');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

function completedFulfillmentFor(Patient $patient, DiagnosticDiscipline $discipline = DiagnosticDiscipline::LAB, ?string $accession = null): DiagnosticFulfillment
{
    $serviceRequest = ServiceRequest::factory()->forPatient($patient)->create();
    $requestItem = RequestItem::factory()->forRequest($serviceRequest)->completed()->create();

    return DiagnosticFulfillment::factory()->create([
        'request_item_id' => $requestItem->id,
        'branch_id' => $serviceRequest->branch_id,
        'discipline' => $discipline,
        'status' => FulfillmentStatus::COMPLETED,
        'accession_number' => $accession ?? 'ACC-'.fake()->unique()->numberBetween(10000, 99999),
    ]);
}

it('lists only completed fulfillments belonging to the patient', function (): void {
    $user = diagnosticWidgetUser($this->branch, ['ViewAny DiagnosticFulfillment']);

    $completed = completedFulfillmentFor($this->patient);

    $pending = completedFulfillmentFor($this->patient);
    $pending->update(['status' => FulfillmentStatus::PENDING]);

    $otherPatient = Patient::withoutEvents(
        fn (): Patient => Patient::factory()->create(['branch_id' => $this->branch->id])
    );
    $otherPatientResult = completedFulfillmentFor($otherPatient);

    Livewire::actingAs($user)
        ->test(CompletedDiagnosticResultsWidget::class, ['patientId' => $this->patient->id])
        ->loadTable()
        ->assertCanSeeTableRecords([$completed])
        ->assertCanNotSeeTableRecords([$pending, $otherPatientResult]);
});

it('shows no records when no patient is in context', function (): void {
    $user = diagnosticWidgetUser($this->branch, ['ViewAny DiagnosticFulfillment']);

    $completed = completedFulfillmentFor($this->patient);

    Livewire::actingAs($user)
        ->test(CompletedDiagnosticResultsWidget::class)
        ->loadTable()
        ->assertCanNotSeeTableRecords([$completed]);
});

it('filters completed results by discipline', function (): void {
    $user = diagnosticWidgetUser($this->branch, ['ViewAny DiagnosticFulfillment']);

    $lab = completedFulfillmentFor($this->patient, DiagnosticDiscipline::LAB);
    $radiology = completedFulfillmentFor($this->patient, DiagnosticDiscipline::RADIOLOGY);

    Livewire::actingAs($user)
        ->test(CompletedDiagnosticResultsWidget::class, ['patientId' => $this->patient->id])
        ->loadTable()
        ->assertCanSeeTableRecords([$lab, $radiology])
        ->filterTable('discipline', DiagnosticDiscipline::RADIOLOGY->value)
        ->assertCanSeeTableRecords([$radiology])
        ->assertCanNotSeeTableRecords([$lab]);
});

it('omits the status filter because the table only lists completed results', function (): void {
    $user = diagnosticWidgetUser($this->branch, ['ViewAny DiagnosticFulfillment']);

    Livewire::actingAs($user)
        ->test(CompletedDiagnosticResultsWidget::class, ['patientId' => $this->patient->id])
        ->loadTable()
        ->assertTableFilterExists('discipline')
        ->assertTableFilterExists('critical')
        ->assertTableFilterExists('created_at');

    expect(array_map(
        fn ($filter): string => $filter->getName(),
        DiagnosticFulfillmentsTable::filters(includeStatus: false),
    ))->not->toContain('status');
});

function submittedResultFor(Patient $patient, User $user): DiagnosticFulfillment
{
    $fulfillment = completedFulfillmentFor($patient);

    DiagnosticObservation::factory()->create([
        'fulfillment_id' => $fulfillment->id,
        'branch_id' => $fulfillment->branch_id,
        'code' => 'HGB',
        'display' => 'Haemoglobin',
        'status' => ObservationStatus::FINAL,
        'value_type' => 'numeric',
        'value_numeric' => 8.4,
        'value_text' => null,
        'units' => 'g/dL',
        'reference_range_min' => 12,
        'reference_range_max' => 16,
        'abnormal_flag' => AbnormalFlag::CRITICALLY_LOW,
        'interpretation' => 'Severe anaemia',
        'performed_by' => $user->id,
        'performed_at' => now(),
        'sort_order' => 0,
    ]);

    DiagnosticResultFile::factory()->create([
        'fulfillment_id' => $fulfillment->id,
        'branch_id' => $fulfillment->branch_id,
        'file_name' => 'haemoglobin-report.pdf',
        'file_path' => 'diagnostics/results/haemoglobin-report.pdf',
        'uploaded_by' => $user->id,
    ]);

    $fulfillment->requestItem->tasks()->create([
        'branch_id' => $fulfillment->branch_id,
        'status' => TaskStatus::COMPLETED,
        'performed_by' => $user->id,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
        'notes' => 'Sample haemolysed, repeat draw taken',
    ]);

    return $fulfillment;
}

it('mounts the view action for permitted users so submitted results can be read', function (): void {
    $user = diagnosticWidgetUser($this->branch, [
        'ViewAny DiagnosticFulfillment',
        'View DiagnosticFulfillment',
    ]);

    $fulfillment = submittedResultFor($this->patient, $user);

    Livewire::actingAs($user)
        ->test(CompletedDiagnosticResultsWidget::class, ['patientId' => $this->patient->id])
        ->loadTable()
        ->assertCanSeeTableRecords([$fulfillment])
        ->mountTableAction('view', $fulfillment)
        ->assertHasNoTableActionErrors()
        ->assertOk();
});

it('renders the submitted observations, files and entry notes in the fulfillment infolist', function (): void {
    $user = diagnosticWidgetUser($this->branch, [
        'ViewAny DiagnosticFulfillment',
        'View DiagnosticFulfillment',
    ]);

    $fulfillment = submittedResultFor($this->patient, $user);

    Gate::before(fn (): bool => true);
    Filament::setCurrentPanel(Filament::getDefaultPanel());
    $this->actingAs($user);

    Livewire::test(ViewDiagnosticFulfillment::class, ['record' => $fulfillment->getKey()])
        ->assertOk()
        ->assertSee('Haemoglobin')
        ->assertSee('8.4 g/dL')
        ->assertSee('12 - 16')
        ->assertSee('Severe anaemia')
        ->assertSee('haemoglobin-report.pdf')
        ->assertSee('Sample haemolysed, repeat draw taken');
});

it('formats observation values and reference ranges from whichever column was filled', function (): void {
    $user = diagnosticWidgetUser($this->branch, ['ViewAny DiagnosticFulfillment']);
    $fulfillment = submittedResultFor($this->patient, $user);

    $observation = $fulfillment->observations()->first();

    expect($observation->value_display)->toBe('8.4 g/dL')
        ->and($observation->reference_range_display)->toBe('12 - 16')
        ->and($observation->isCritical())->toBeTrue();

    $textual = DiagnosticObservation::factory()->create([
        'fulfillment_id' => $fulfillment->id,
        'branch_id' => $fulfillment->branch_id,
        'value_type' => 'text',
        'value_numeric' => null,
        'value_text' => 'No growth after 48 hours',
        'units' => null,
        'reference_range_min' => null,
        'reference_range_max' => null,
        'reference_range_text' => 'No growth',
    ]);

    expect($textual->value_display)->toBe('No growth after 48 hours')
        ->and($textual->reference_range_display)->toBe('No growth')
        ->and($textual->isCritical())->toBeFalse();
});

it('hides the view action from users without the view permission', function (): void {
    $user = diagnosticWidgetUser($this->branch, ['ViewAny DiagnosticFulfillment']);

    $fulfillment = completedFulfillmentFor($this->patient);

    Livewire::actingAs($user)
        ->test(CompletedDiagnosticResultsWidget::class, ['patientId' => $this->patient->id])
        ->loadTable()
        ->assertCanSeeTableRecords([$fulfillment])
        ->assertTableActionHidden('view', $fulfillment);
});

it('is offered to the clinical workspace whenever the diagnostics module is installed', function (): void {
    $this->actingAs(diagnosticWidgetUser($this->branch));

    expect(app(ClinicalWorkspace::class)->completedResultsWidget())
        ->toBe(CompletedDiagnosticResultsWidget::class);
});

it('is visible to every clinical user who can reach the workspace', function (): void {
    $this->actingAs(diagnosticWidgetUser($this->branch));

    expect(CompletedDiagnosticResultsWidget::canView())->toBeTrue();
});
