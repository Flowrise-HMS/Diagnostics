<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Clinical\Classes\Services\FulfillmentService;
use Modules\Clinical\Filament\Widgets\PendingFulfillmentsWidget;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Enums\ServiceCategoryCode;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ViewDiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticResultFile;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Staff', 'Diagnostics']);

    $this->branch = Branch::factory()->default()->create();
    $this->patient = Patient::withoutEvents(
        fn (): Patient => Patient::factory()->create(['branch_id' => $this->branch->id])
    );

    $this->service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::LAB->value]))
        ->create([
            'name' => 'Full Blood Count (FBC)',
            'requires_payment_before' => false,
        ]);

    DiagnosticServiceProfile::create([
        'service_id' => $this->service->id,
        'discipline' => DiagnosticDiscipline::LAB,
        'default_specimen_type' => 'blood',
        'is_active' => true,
    ]);

    $serviceRequest = ServiceRequest::factory()->forPatient($this->patient)->create();

    $this->requestItem = RequestItem::factory()
        ->forRequest($serviceRequest)
        ->forService($this->service)
        ->create();

    $this->fulfillment = DiagnosticFulfillment::query()
        ->where('request_item_id', $this->requestItem->id)
        ->firstOrFail();

    /*
     * The seeded diagnostic catalog carries no branch_id, and BelongsToBranch scopes
     * every read to the acting user's branch, so a branch-assigned user cannot resolve
     * a service profile at all. Mirror the unscoped users the app is used with today.
     */
    $this->user = User::factory()->create(['branch_id' => null]);

    Gate::before(fn (): bool => true);
    Filament::setCurrentPanel(Filament::getDefaultPanel());
    $this->actingAs($this->user);
});

it('stores a file uploaded through the structured results action', function (): void {
    Livewire::test(ViewDiagnosticFulfillment::class, ['record' => $this->fulfillment->getKey()])
        ->mountAction('recordStructuredResults')
        ->setActionData([
            'result_files' => [UploadedFile::fake()->create('fbc-report.pdf', 100, 'application/pdf')],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    assertStoredResultFile($this->fulfillment);
});

it('stores a file uploaded through the pending fulfillments widget', function (): void {
    expect(DiagnosticServiceProfile::query()->count())->toBe(1)
        ->and(app(DiagnosticResultService::class)->getProfile($this->requestItem))->not->toBeNull()
        ->and($this->requestItem->service?->category?->code)->toBe(ServiceCategoryCode::LAB)
        ->and($this->requestItem->prescriptionDetail)->toBeNull()
        ->and(app(FulfillmentService::class)->getType($this->requestItem))->toBe('diagnostic');

    Livewire::test(PendingFulfillmentsWidget::class, ['patientId' => $this->patient->id])
        ->loadTable()
        ->assertCanSeeTableRecords([$this->requestItem])
        ->assertTableActionVisible('fulfill', $this->requestItem)
        ->mountTableAction('fulfill', $this->requestItem)
        ->setTableActionData([
            'result_files' => [UploadedFile::fake()->create('fbc-report.pdf', 100, 'application/pdf')],
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    assertStoredResultFile($this->fulfillment);
});

it('discards an unresolved livewire placeholder instead of recording it as a path', function (): void {
    app(DiagnosticResultService::class)->submit($this->requestItem, [
        'result_files' => ['livewire-file:zixX1aRBlHV3fT3qO0o49T6DKaGygK3XtaF5OX8H.pdf'],
    ], $this->user);

    expect(DiagnosticResultFile::query()->where('fulfillment_id', $this->fulfillment->id)->exists())
        ->toBeFalse();
});

it('discards a submitted path that does not exist on the result files disk', function (): void {
    app(DiagnosticResultService::class)->submit($this->requestItem, [
        'result_files' => ['../../../.env'],
    ], $this->user);

    expect(DiagnosticResultFile::query()->where('fulfillment_id', $this->fulfillment->id)->exists())
        ->toBeFalse();
});

it('serves a stored result file through a signed download route', function (): void {
    Storage::fake(config('diagnostics.result_files.disk'));

    $resultFile = DiagnosticResultFile::factory()->create([
        'fulfillment_id' => $this->fulfillment->id,
        'branch_id' => $this->fulfillment->branch_id,
        'file_name' => 'fbc-report.pdf',
        'file_path' => 'diagnostics/results/fbc-report.pdf',
        'uploaded_by' => $this->user->id,
    ]);

    Storage::disk(config('diagnostics.result_files.disk'))
        ->put('diagnostics/results/fbc-report.pdf', 'report bytes');

    $this->get($resultFile->downloadUrl())->assertSuccessful();
});

it('rejects a result file download without a valid signature', function (): void {
    $resultFile = DiagnosticResultFile::factory()->create([
        'fulfillment_id' => $this->fulfillment->id,
        'branch_id' => $this->fulfillment->branch_id,
        'file_name' => 'fbc-report.pdf',
        'file_path' => 'diagnostics/results/fbc-report.pdf',
        'uploaded_by' => $this->user->id,
    ]);

    $this->get(route('diagnostics.result-files.download', ['resultFile' => $resultFile->getKey()]))
        ->assertForbidden();
});

function assertStoredResultFile(DiagnosticFulfillment $fulfillment): void
{
    $resultFile = DiagnosticResultFile::query()
        ->where('fulfillment_id', $fulfillment->id)
        ->firstOrFail();

    expect($resultFile->file_path)
        ->not->toStartWith('livewire-file:')
        ->not->toStartWith('livewire-files:')
        ->and($resultFile->file_name)->toBe('fbc-report.pdf')
        ->and($resultFile->mime_type)->toBe('application/pdf')
        ->and(Storage::disk(config('diagnostics.result_files.disk'))->exists($resultFile->file_path))
        ->toBeTrue();
}
