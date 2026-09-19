<?php

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Enums\ServiceCategoryCode;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Filament\Schemas\DiagnosticResultEntryForm;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Staff', 'Diagnostics']);

    $this->branch = Branch::factory()->default()->create();
    $this->patient = Patient::withoutEvents(
        fn (): Patient => Patient::factory()->create(['branch_id' => $this->branch->id])
    );
});

function entryFormItem(string $categoryCode, array $fields = []): RequestItem
{
    $service = Service::factory()
        ->forCategory(test()->serviceCategory(['code' => $categoryCode]))
        ->create(['requires_payment_before' => false]);

    $profile = test()->diagnosticProfileFor($service, [
        'discipline' => DiagnosticDiscipline::fromServiceCategoryCode($categoryCode),
    ]);

    if ($fields !== []) {
        $template = DiagnosticResultTemplate::query()->create([
            'profile_id' => $profile->id,
            'name' => 'Default',
            'is_default' => true,
            'is_active' => true,
        ]);

        foreach ($fields as $index => $field) {
            $template->fields()->create($field + ['sort_order' => $index]);
        }
    }

    $serviceRequest = ServiceRequest::factory()->forPatient(test()->patient)->create();

    return RequestItem::factory()->forRequest($serviceRequest)->forService($service)->create();
}

/**
 * @return array<string, Component>
 */
function flattenComponents(array $components): array
{
    $flat = [];

    foreach ($components as $component) {
        if ($component instanceof Grid) {
            $flat += flattenComponents($component->getDefaultChildComponents());

            continue;
        }

        $flat[$component->getName()] = $component;
    }

    return $flat;
}

it('renders structured fields with units, normal range hints and required flags', function (): void {
    $item = entryFormItem(ServiceCategoryCode::LAB->value, [
        ['field_key' => 'hemoglobin', 'label' => 'Haemoglobin', 'value_type' => 'numeric', 'default_units' => 'g/dL', 'reference_range_low' => 12, 'reference_range_high' => 17.5, 'is_required' => true],
        ['field_key' => 'comment', 'label' => 'Comment', 'value_type' => 'long_text'],
        ['field_key' => 'result', 'label' => 'Result', 'value_type' => 'select', 'options' => ['Positive', 'Negative']],
        ['field_key' => 'method', 'label' => 'Method', 'value_type' => 'text'],
    ]);

    $components = flattenComponents(DiagnosticResultEntryForm::components($item));

    $hb = $components['field_hemoglobin'];
    expect($hb)->toBeInstanceOf(TextInput::class)
        ->and($hb->getSuffixLabel())->toBe('g/dL')
        ->and($hb->isRequired())->toBeTrue();

    expect($components['field_comment'])->toBeInstanceOf(Textarea::class)
        ->and($components['field_comment']->isRequired())->toBeFalse()
        ->and($components['field_result'])->toBeInstanceOf(Select::class)
        ->and($components['field_result']->getOptions())->toBe(['Positive' => 'Positive', 'Negative' => 'Negative'])
        ->and($components['field_method'])->toBeInstanceOf(TextInput::class)
        ->and($components)->not->toHaveKey('report_conclusion')
        ->and($components)->not->toHaveKey('results')
        ->and($components['result_files'])->toBeInstanceOf(FileUpload::class)
        ->and($components['result_files']->isRequired())->toBeFalse()
        ->and($components['notes'])->toBeInstanceOf(Textarea::class);
});

it('describes the normal range of a numeric field', function (): void {
    $field = new DiagnosticResultTemplateField([
        'value_type' => 'numeric',
        'default_units' => 'g/dL',
        'reference_range_low' => 12,
        'reference_range_high' => 17.5,
    ]);

    expect(DiagnosticResultEntryForm::normalRangeHint($field))->toBe('Normal: 12 - 17.5 g/dL');

    $field->fill(['reference_range_high' => null]);
    expect(DiagnosticResultEntryForm::normalRangeHint($field))->toBe('Normal: >= 12 g/dL');

    $field->fill(['reference_range_low' => null, 'reference_range_high' => 99, 'default_units' => null]);
    expect(DiagnosticResultEntryForm::normalRangeHint($field))->toBe('Normal: <= 99');

    $field->fill(['reference_range_high' => null]);
    expect(DiagnosticResultEntryForm::normalRangeHint($field))->toBeNull();

    // Units that start with a digit would otherwise read as part of the number.
    $field->fill(['reference_range_low' => 4, 'reference_range_high' => 11, 'default_units' => '10*9/L']);
    expect(DiagnosticResultEntryForm::normalRangeHint($field))->toBe('Normal: 4 - 11 (10*9/L)');
});

it('renders a findings textarea and file upload when the service has no result fields', function (): void {
    $item = entryFormItem(ServiceCategoryCode::RAD->value);

    $components = flattenComponents(DiagnosticResultEntryForm::components($item));

    expect($components['report_conclusion'])->toBeInstanceOf(Textarea::class)
        ->and($components['report_conclusion']->getLabel())->toBe('Findings / Result')
        ->and($components['result_files'])->toBeInstanceOf(FileUpload::class)
        ->and($components['notes'])->toBeInstanceOf(Textarea::class)
        ->and(collect($components)->filter(fn ($component) => $component instanceof Repeater))->toBeEmpty();
});
