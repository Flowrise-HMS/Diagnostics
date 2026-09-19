<?php

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Core\Enums\ServiceCategoryCode;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\CreateDiagnosticServiceProfile;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\EditDiagnosticServiceProfile;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ListDiagnosticServiceProfiles;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\Pages\ViewDiagnosticServiceProfile;
use Modules\Diagnostics\Filament\Clusters\Diagnostics\Resources\DiagnosticServiceProfiles\RelationManagers\DiagnosticReferenceRangesRelationManager;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Staff', 'Diagnostics']);

    Gate::before(fn (): bool => true);
    Filament::setCurrentPanel(Filament::getDefaultPanel());
    $this->actingAs(User::factory()->create(['branch_id' => null]));
});

afterEach(function (): void {
    Model::preventLazyLoading(false);
});

it('creates a diagnostic service with inline result fields', function (): void {
    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::CON->value]))
        ->create(['name' => 'Wound swab MCS']);

    Livewire::test(CreateDiagnosticServiceProfile::class)
        ->fillForm([
            'service_id' => $service->id,
            'discipline' => DiagnosticDiscipline::LAB->value,
            'default_specimen_type' => 'swab',
            'result_fields' => [
                ['label' => 'Organism isolated', 'field_key' => 'organism', 'value_type' => 'text'],
                ['label' => 'Colony count', 'field_key' => 'colony_count', 'value_type' => 'numeric', 'default_units' => 'CFU/mL', 'reference_range_low' => 0, 'reference_range_high' => 1000, 'is_required' => true],
                ['label' => 'Sensitivity', 'field_key' => 'sensitivity', 'value_type' => 'select', 'options' => ['Sensitive', 'Resistant']],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $profile = DiagnosticServiceProfile::query()->where('service_id', $service->id)->firstOrFail();
    $fields = $profile->resultFields()->get();

    expect($profile->discipline)->toBe(DiagnosticDiscipline::LAB)
        ->and($profile->default_specimen_type)->toBe('swab')
        ->and(DiagnosticResultTemplate::query()->where('profile_id', $profile->id)->where('is_default', true)->count())->toBe(1)
        ->and($fields->pluck('field_key')->all())->toBe(['organism', 'colony_count', 'sensitivity'])
        ->and($fields->pluck('sort_order')->all())->toBe([0, 1, 2])
        ->and($fields[1]->is_required)->toBeTrue()
        ->and((float) $fields[1]->reference_range_high)->toBe(1000.0)
        ->and($fields[2]->options)->toBe(['Sensitive', 'Resistant']);
});

it('rejects duplicate field keys', function (): void {
    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::CON->value]))
        ->create();

    Livewire::test(CreateDiagnosticServiceProfile::class)
        ->fillForm([
            'service_id' => $service->id,
            'discipline' => DiagnosticDiscipline::LAB->value,
            'result_fields' => [
                ['label' => 'A', 'field_key' => 'same', 'value_type' => 'text'],
                ['label' => 'B', 'field_key' => 'same', 'value_type' => 'text'],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(DiagnosticServiceProfile::query()->where('service_id', $service->id)->exists())->toBeFalse();
});

it('derives a field key from the label', function (): void {
    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::LAB->value]))
        ->create();
    $profile = DiagnosticServiceProfile::query()->where('service_id', $service->id)->firstOrFail();

    $template = app(DiagnosticCatalogService::class)->syncResultFields($profile, [
        ['label' => 'PCV / Hematocrit', 'value_type' => 'numeric'],
    ]);

    expect($template->fields->first()->field_key)->toBe('pcv_hematocrit');
});

it('edits result fields in place, reorders them and drops removed fields with their ranges', function (): void {
    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::LAB->value]))
        ->create();
    $profile = DiagnosticServiceProfile::query()->where('service_id', $service->id)->firstOrFail();

    $template = app(DiagnosticCatalogService::class)->syncResultFields($profile, [
        ['label' => 'Haemoglobin', 'field_key' => 'hemoglobin', 'value_type' => 'numeric'],
        ['label' => 'Platelets', 'field_key' => 'platelets', 'value_type' => 'numeric'],
    ]);
    [$hb, $plt] = $template->fields->all();

    DiagnosticReferenceRange::query()->create([
        'profile_id' => $profile->id,
        'template_field_id' => $plt->id,
        'gender' => 'any',
        'min_value' => 150,
        'max_value' => 400,
    ]);

    $page = Livewire::test(EditDiagnosticServiceProfile::class, ['record' => $profile->getKey()])
        ->assertFormFieldDisabled('service_id')
        ->assertFormSet(fn (array $state): bool => count($state['result_fields']) === 2);

    $page->fillForm([
        'result_fields' => [
            ['id' => null, 'label' => 'White cells', 'field_key' => 'wbc', 'value_type' => 'numeric'],
            ['id' => $hb->id, 'label' => 'Haemoglobin (Hb)', 'field_key' => 'hemoglobin', 'value_type' => 'numeric', 'default_units' => 'g/dL'],
        ],
    ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fields = $profile->resultFields()->get();

    expect($fields->pluck('field_key')->all())->toBe(['wbc', 'hemoglobin'])
        ->and($fields->firstWhere('field_key', 'hemoglobin')->id)->toBe($hb->id)
        ->and($fields->firstWhere('field_key', 'hemoglobin')->label)->toBe('Haemoglobin (Hb)')
        ->and(DiagnosticReferenceRange::query()->where('template_field_id', $plt->id)->exists())->toBeFalse();
});

it('renders the list and view pages without lazy loading', function (): void {
    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::RAD->value]))
        ->create();
    $profile = DiagnosticServiceProfile::query()->where('service_id', $service->id)->firstOrFail();
    app(DiagnosticCatalogService::class)->syncResultFields($profile, [
        ['label' => 'Findings', 'value_type' => 'long_text'],
    ]);

    Model::preventLazyLoading();

    Livewire::test(ListDiagnosticServiceProfiles::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$profile]);

    Livewire::test(ViewDiagnosticServiceProfile::class, ['record' => $profile->getKey()])
        ->assertOk()
        ->assertSee('Findings');
});

it('requires a numeric result field when adding a reference range', function (): void {
    $service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::LAB->value]))
        ->create();
    $profile = DiagnosticServiceProfile::query()->where('service_id', $service->id)->firstOrFail();
    $template = app(DiagnosticCatalogService::class)->syncResultFields($profile, [
        ['label' => 'Glucose', 'field_key' => 'glucose', 'value_type' => 'numeric', 'default_units' => 'mg/dL'],
        ['label' => 'Sample', 'field_key' => 'sample', 'value_type' => 'text'],
    ]);
    $glucose = $template->fields->firstWhere('field_key', 'glucose');

    $manager = fn () => Livewire::test(DiagnosticReferenceRangesRelationManager::class, [
        'ownerRecord' => $profile,
        'pageClass' => EditDiagnosticServiceProfile::class,
    ]);

    $manager()->callAction(TestAction::make('create')->table(), data: [
        'gender' => 'any',
        'min_value' => 70,
        'max_value' => 99,
    ])->assertHasActionErrors(['template_field_id']);

    $manager()->callAction(TestAction::make('create')->table(), data: [
        'template_field_id' => $glucose->id,
        'gender' => 'any',
        'min_value' => 70,
        'max_value' => 99,
        'units' => 'mg/dL',
    ])->assertHasNoActionErrors();

    expect(DiagnosticReferenceRange::query()->where('template_field_id', $glucose->id)->count())->toBe(1)
        ->and($profile->numericResultFieldOptions())->toBe([$glucose->id => 'Glucose']);
});
