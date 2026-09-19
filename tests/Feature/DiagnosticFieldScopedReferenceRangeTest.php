<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Clinical\Models\RequestItem;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Enums\ServiceCategoryCode;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Classes\Services\DiagnosticCatalogService;
use Modules\Diagnostics\Classes\Services\DiagnosticResultService;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Patient\Models\Patient;
use Tests\TestCase;

uses(TestCase::class, DatabaseTransactions::class);

beforeEach(function (): void {
    $this->migrateModules(['Core', 'Patient', 'Clinical', 'Staff', 'Diagnostics']);

    $this->branch = Branch::factory()->default()->create();
    $this->user = User::factory()->create(['branch_id' => null]);

    $this->service = Service::factory()
        ->forCategory($this->serviceCategory(['code' => ServiceCategoryCode::LAB->value]))
        ->create(['name' => 'Full Blood Count', 'requires_payment_before' => false]);

    $this->profile = $this->diagnosticProfileFor($this->service, ['discipline' => 'lab']);

    $template = app(DiagnosticCatalogService::class)->syncResultFields($this->profile, [
        ['label' => 'Haemoglobin', 'field_key' => 'hemoglobin', 'value_type' => 'numeric', 'default_units' => 'g/dL', 'reference_range_low' => 11, 'reference_range_high' => 16],
        ['label' => 'White cells', 'field_key' => 'wbc', 'value_type' => 'numeric', 'default_units' => '10*9/L', 'reference_range_low' => 4, 'reference_range_high' => 11],
    ]);

    $this->hbField = $template->fields->firstWhere('field_key', 'hemoglobin');
    $this->wbcField = $template->fields->firstWhere('field_key', 'wbc');
});

function observationsFor(Patient $patient, array $values): array
{
    $request = ServiceRequest::factory()->forPatient($patient)->create();
    $item = RequestItem::factory()->forRequest($request)->forService(test()->service)->create();

    app(DiagnosticResultService::class)->submit($item, $values, test()->user);

    return DiagnosticObservation::query()
        ->whereHas('fulfillment', fn ($query) => $query->where('request_item_id', $item->id))
        ->get()
        ->keyBy('code')
        ->all();
}

it('applies a field-scoped range only to its own field', function (): void {
    DiagnosticReferenceRange::query()->create([
        'profile_id' => $this->profile->id,
        'template_field_id' => $this->hbField->id,
        'gender' => 'male',
        'age_min_months' => 216,
        'min_value' => 13.5,
        'max_value' => 17.5,
        'units' => 'g/dL',
        'critical_low' => 7,
        'critical_high' => 20,
    ]);

    $patient = Patient::factory()->create([
        'branch_id' => $this->branch->id,
        'gender' => 'male',
        'date_of_birth' => now()->subYears(30),
    ]);

    $observations = observationsFor($patient, ['field_hemoglobin' => '12.5', 'field_wbc' => '12.5']);

    // Haemoglobin uses the male adult range, so 12.5 is low.
    expect((float) $observations['hemoglobin']->reference_range_min)->toBe(13.5)
        ->and($observations['hemoglobin']->abnormal_flag)->toBe(AbnormalFlag::LOW);

    // White cells keep their own field defaults instead of inheriting the haemoglobin range.
    expect((float) $observations['wbc']->reference_range_min)->toBe(4.0)
        ->and((float) $observations['wbc']->reference_range_max)->toBe(11.0)
        ->and($observations['wbc']->units)->toBe('10*9/L')
        ->and($observations['wbc']->abnormal_flag)->toBe(AbnormalFlag::HIGH);
});

it('matches ranges by sex and age and falls back to the field defaults otherwise', function (): void {
    DiagnosticReferenceRange::query()->create([
        'profile_id' => $this->profile->id,
        'template_field_id' => $this->hbField->id,
        'gender' => 'female',
        'age_min_months' => 216,
        'min_value' => 12,
        'max_value' => 15.5,
        'units' => 'g/dL',
    ]);

    $adultWoman = Patient::factory()->create([
        'branch_id' => $this->branch->id,
        'gender' => 'female',
        'date_of_birth' => now()->subYears(40),
    ]);
    $girl = Patient::factory()->create([
        'branch_id' => $this->branch->id,
        'gender' => 'female',
        'date_of_birth' => now()->subYears(5),
    ]);

    $adult = observationsFor($adultWoman, ['field_hemoglobin' => '11.5']);
    $child = observationsFor($girl, ['field_hemoglobin' => '11.5']);

    expect((float) $adult['hemoglobin']->reference_range_min)->toBe(12.0)
        ->and($adult['hemoglobin']->abnormal_flag)->toBe(AbnormalFlag::LOW)
        ->and((float) $child['hemoglobin']->reference_range_min)->toBe(11.0)
        ->and($child['hemoglobin']->abnormal_flag)->toBe(AbnormalFlag::NORMAL);
});

it('never applies a profile-wide range to a multi-field service', function (): void {
    DiagnosticReferenceRange::query()->create([
        'profile_id' => $this->profile->id,
        'template_field_id' => null,
        'gender' => 'any',
        'min_value' => 100,
        'max_value' => 200,
        'units' => 'legacy',
    ]);

    $patient = Patient::factory()->create(['branch_id' => $this->branch->id, 'gender' => 'male', 'date_of_birth' => now()->subYears(30)]);

    $observations = observationsFor($patient, ['field_hemoglobin' => '14', 'field_wbc' => '6']);

    expect($observations['hemoglobin']->abnormal_flag)->toBe(AbnormalFlag::NORMAL)
        ->and((float) $observations['hemoglobin']->reference_range_max)->toBe(16.0)
        ->and($observations['wbc']->units)->toBe('10*9/L');
});

it('deletes a field\'s ranges when the field is removed from the service', function (): void {
    DiagnosticReferenceRange::query()->create([
        'profile_id' => $this->profile->id,
        'template_field_id' => $this->wbcField->id,
        'gender' => 'any',
        'min_value' => 4,
        'max_value' => 11,
    ]);

    app(DiagnosticCatalogService::class)->syncResultFields($this->profile, [
        ['id' => $this->hbField->id, 'label' => 'Haemoglobin', 'field_key' => 'hemoglobin', 'value_type' => 'numeric'],
    ]);

    expect(DiagnosticReferenceRange::query()->where('template_field_id', $this->wbcField->id)->exists())->toBeFalse()
        ->and($this->profile->resultFields()->count())->toBe(1);
});
