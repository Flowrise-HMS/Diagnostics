<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Models\Branch;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Filament\Widgets\PendingDiagnosticFulfillmentsWidget;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
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

function pendingDiagnosticWidgetUser(Branch $branch, array $permissions = []): User
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

function pendingFulfillmentFor(Patient $patient, FulfillmentStatus $status = FulfillmentStatus::PENDING): DiagnosticFulfillment
{
    $serviceRequest = ServiceRequest::factory()->forPatient($patient)->create();
    $requestItem = RequestItem::factory()->forRequest($serviceRequest)->create();

    return DiagnosticFulfillment::factory()->create([
        'request_item_id' => $requestItem->id,
        'branch_id' => $serviceRequest->branch_id,
        'discipline' => DiagnosticDiscipline::LAB,
        'status' => $status,
        'accession_number' => 'ACC-'.fake()->unique()->numberBetween(10000, 99999),
    ]);
}

it('lists only active diagnostic fulfillments for the patient', function (): void {
    $user = pendingDiagnosticWidgetUser($this->branch, ['ViewAny DiagnosticFulfillment']);

    $pending = pendingFulfillmentFor($this->patient, FulfillmentStatus::PENDING);
    $inProgress = pendingFulfillmentFor($this->patient, FulfillmentStatus::IN_PROGRESS);
    $completed = pendingFulfillmentFor($this->patient, FulfillmentStatus::COMPLETED);

    $otherPatient = Patient::withoutEvents(
        fn (): Patient => Patient::factory()->create(['branch_id' => $this->branch->id])
    );
    $otherPending = pendingFulfillmentFor($otherPatient, FulfillmentStatus::PENDING);

    Livewire::actingAs($user)
        ->test(PendingDiagnosticFulfillmentsWidget::class, ['patientId' => $this->patient->id])
        ->loadTable()
        ->assertCanSeeTableRecords([$pending, $inProgress])
        ->assertCanNotSeeTableRecords([$completed, $otherPending]);
});

it('shows an empty table when no patient is provided', function (): void {
    $user = pendingDiagnosticWidgetUser($this->branch, ['ViewAny DiagnosticFulfillment']);
    pendingFulfillmentFor($this->patient, FulfillmentStatus::PENDING);

    Livewire::actingAs($user)
        ->test(PendingDiagnosticFulfillmentsWidget::class)
        ->loadTable()
        ->assertCanNotSeeTableRecords(DiagnosticFulfillment::query()->get());
});

it('is visible whenever the diagnostics module can render workspace widgets', function (): void {
    $this->actingAs(pendingDiagnosticWidgetUser($this->branch));

    expect(PendingDiagnosticFulfillmentsWidget::canView())->toBeTrue();
});
