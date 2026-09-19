<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Enums\ServiceCategoryCode;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Exceptions\EmptyDiagnosticResultException;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticFulfillments\Pages\ViewDiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Staff', 'Diagnostics']);

    $this->branch = Branch::factory()->default()->create();
    $this->patient = Patient::withoutEvents(
        fn (): Patient => Patient::factory()->create(['branch_id' => $this->branch->id])
    );

    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::RAD->value]))
        ->create(['name' => 'Chest X-Ray', 'requires_payment_before' => false]);
    $this->diagnosticProfileFor($service, ['discipline' => DiagnosticDiscipline::RADIOLOGY]);

    $serviceRequest = ServiceRequest::factory()->forPatient($this->patient)->create();
    $this->item = RequestItem::factory()->forRequest($serviceRequest)->forService($service)->create();
    $this->fulfillment = DiagnosticFulfillment::query()->where('request_item_id', $this->item->id)->firstOrFail();

    $this->user = User::factory()->create(['branch_id' => null]);
    $this->actingAs($this->user);
});

it('refuses a submission with no value, findings or file and writes nothing', function (): void {
    $service = app(DiagnosticResultService::class);

    expect(fn () => $service->submit($this->item, ['notes' => 'just a note'], $this->user))
        ->toThrow(EmptyDiagnosticResultException::class);

    expect($this->item->fresh()->tasks()->count())->toBe(0)
        ->and($this->fulfillment->fresh()->reportVersions()->count())->toBe(0)
        ->and($this->fulfillment->fresh()->status->value)->toBe('pending');
});

it('accepts findings only', function (): void {
    app(DiagnosticResultService::class)->submit($this->item, ['report_conclusion' => 'Clear lung fields.'], $this->user);

    expect($this->fulfillment->fresh()->latestReportVersion?->conclusion)->toBe('Clear lung fields.')
        ->and($this->item->fresh()->tasks()->latest()->first()?->results)->toMatchArray([
            'conclusion' => ['label' => 'Findings / Result', 'value' => 'Clear lung fields.', 'type' => 'long_text'],
        ]);
});

it('accepts a file only', function (): void {
    Storage::fake(config('diagnostics.result_files.disk'));

    app(DiagnosticResultService::class)->submit($this->item, [
        'result_files' => [UploadedFile::fake()->image('chest.jpg')],
    ], $this->user);

    expect($this->fulfillment->fresh()->resultFiles()->count())->toBe(1)
        ->and($this->fulfillment->fresh()->status->value)->toBe('completed');
});

it('shows a danger notification instead of an exception from the record results action', function (): void {
    Gate::before(fn (): bool => true);
    Filament::setCurrentPanel(Filament::getDefaultPanel());

    Livewire::test(ViewDiagnosticFulfillment::class, ['record' => $this->fulfillment->getKey()])
        ->mountAction('recordStructuredResults')
        ->setActionData(['notes' => 'nothing else'])
        ->callMountedAction()
        ->assertNotified('Results were not recorded');

    expect($this->fulfillment->fresh()->status->value)->toBe('pending');
});
