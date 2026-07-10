<?php

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Modules\Diagnostics\Classes\Fhir\FhirObservationTransformer;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\ObservationStatus;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticObservationComponent;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->transformer = new FhirObservationTransformer;
});

function createObservation(array $overrides = []): DiagnosticObservation
{
    $defaults = [
        'id' => (string) Str::uuid(),
        'status' => ObservationStatus::FINAL,
        'code' => '718-7',
        'display' => 'Hemoglobin',
        'value_type' => null,
        'value_quantity_value' => null,
        'value_quantity_unit' => null,
        'value_text' => null,
        'value_coded' => null,
        'value_boolean' => null,
        'value_numeric' => null,
        'units' => null,
        'value_range_low' => null,
        'value_range_high' => null,
        'data_absent_reason' => null,
        'abnormal_flag' => null,
        'notes' => null,
        'specimen_id' => null,
        'performed_at' => null,
        'verified_at' => null,
        'performed_by' => null,
        'reference_range_min' => null,
        'reference_range_max' => null,
        'reference_range_text' => null,
        'fulfillment' => null,
        'components' => null,
        'childObservations' => null,
        'performedBy' => null,
    ];

    $attrs = array_merge($defaults, $overrides);

    $model = new DiagnosticObservation;

    $attributeKeys = [
        'id', 'status', 'code', 'display', 'value_type',
        'value_quantity_value', 'value_quantity_unit', 'value_text', 'value_coded',
        'value_boolean', 'value_numeric', 'units', 'value_range_low', 'value_range_high',
        'data_absent_reason', 'abnormal_flag', 'notes', 'specimen_id',
        'performed_at', 'verified_at', 'performed_by',
        'reference_range_min', 'reference_range_max', 'reference_range_text',
    ];

    foreach ($attributeKeys as $key) {
        if (array_key_exists($key, $attrs)) {
            $model->setAttribute($key, $attrs[$key]);
        }
    }

    foreach (['fulfillment', 'performedBy'] as $relation) {
        if (array_key_exists($relation, $attrs)) {
            $model->setRelation($relation, $attrs[$relation]);
        }
    }

    $model->setRelation('components', $attrs['components'] ?? new Collection);
    $model->setRelation('childObservations', $attrs['childObservations'] ?? new Collection);

    return $model;
}

function createComponent(array $overrides = []): DiagnosticObservationComponent
{
    $defaults = [
        'code' => '718-7',
        'display' => 'Hemoglobin',
        'value_type' => null,
        'value_quantity_value' => null,
        'value_quantity_unit' => null,
        'value_text' => null,
        'value_coded' => null,
        'value_boolean' => null,
        'value_numeric' => null,
        'units' => null,
        'value_range_low' => null,
        'value_range_high' => null,
        'data_absent_reason' => null,
        'abnormal_flag' => null,
        'reference_range_min' => null,
        'reference_range_max' => null,
        'reference_range_text' => null,
    ];

    $attrs = array_merge($defaults, $overrides);

    $model = new DiagnosticObservationComponent;

    foreach ($attrs as $key => $value) {
        $model->setAttribute($key, $value);
    }

    return $model;
}

function createFulfillmentChain(?string $patientId = null, ?string $encounterId = null, ?string $serviceRequestId = null): object
{
    $serviceRequest = Mockery::mock('stdClass');
    $serviceRequest->id = $serviceRequestId;
    $serviceRequest->patient_id = $patientId;
    $serviceRequest->encounter_id = $encounterId;

    $requestItem = Mockery::mock('stdClass');
    $requestItem->serviceRequest = $serviceRequest;

    $fulfillment = Mockery::mock('stdClass');
    $fulfillment->requestItem = $requestItem;

    return $fulfillment;
}

it('returns resource type', function () {
    expect($this->transformer->resourceType())->toBe('Observation');
});

it('transforms basic observation with status, category, and code', function () {
    $observation = createObservation();

    $result = $this->transformer->toFhir($observation);

    expect($result['resourceType'])->toBe('Observation');
    expect($result['status'])->toBe('final');
    expect($result['category'][0]['coding'][0]['code'])->toBe('laboratory');
    expect($result['code']['coding'][0]['code'])->toBe('718-7');
    expect($result['code']['coding'][0]['display'])->toBe('Hemoglobin');
});

it('includes subject and encounter from fulfillment chain', function () {
    $fulfillment = createFulfillmentChain(
        patientId: 'patient-uuid',
        encounterId: 'encounter-uuid',
    );
    $observation = createObservation(['fulfillment' => $fulfillment]);

    $result = $this->transformer->toFhir($observation);

    expect($result['subject']['reference'])->toBe('Patient/patient-uuid');
    expect($result['encounter']['reference'])->toBe('Encounter/encounter-uuid');
});

it('skips subject when fulfillment chain is incomplete', function () {
    $observation = createObservation();

    $result = $this->transformer->toFhir($observation);

    expect($result)->not->toHaveKey('subject');
    expect($result)->not->toHaveKey('encounter');
});

it('includes valueQuantity', function () {
    $observation = createObservation([
        'value_type' => 'quantity',
        'value_quantity_value' => 15.5,
        'value_quantity_unit' => 'g/dL',
    ]);

    $result = $this->transformer->toFhir($observation);

    expect($result['valueQuantity']['value'])->toBe(15.5);
    expect($result['valueQuantity']['unit'])->toBe('g/dL');
    expect($result['valueQuantity']['system'])->toBe('http://unitsofmeasure.org');
});

it('includes valueString', function () {
    $observation = createObservation([
        'code' => '48004-3',
        'display' => 'Urine color',
        'value_type' => 'text',
        'value_text' => 'Yellow',
    ]);

    $result = $this->transformer->toFhir($observation);
    expect($result['valueString'])->toBe('Yellow');
});

it('includes valueCodeableConcept', function () {
    $observation = createObservation([
        'code' => '33961-6',
        'display' => 'Blood type',
        'value_type' => 'coded',
        'value_coded' => 'A+',
        'value_text' => 'A Positive',
    ]);

    $result = $this->transformer->toFhir($observation);
    expect($result['valueCodeableConcept']['coding'][0]['code'])->toBe('A+');
    expect($result['valueCodeableConcept']['text'])->toBe('A Positive');
});

it('includes valueBoolean', function () {
    $observation = createObservation([
        'code' => '29762-2',
        'display' => 'Pregnant',
        'value_type' => 'boolean',
        'value_boolean' => true,
    ]);

    $result = $this->transformer->toFhir($observation);
    expect($result['valueBoolean'])->toBeTrue();
});

it('includes valueInteger for numeric without units', function () {
    $observation = createObservation([
        'code' => '8339-4',
        'display' => 'Body mass index',
        'value_type' => 'numeric',
        'value_numeric' => 24,
    ]);

    $result = $this->transformer->toFhir($observation);
    expect($result['valueInteger'])->toBe(24);
});

it('includes valueQuantity for numeric with units', function () {
    $observation = createObservation([
        'code' => '29463-7',
        'display' => 'Body weight',
        'value_type' => 'numeric',
        'value_numeric' => 75.5,
        'units' => 'kg',
    ]);

    $result = $this->transformer->toFhir($observation);
    expect($result['valueQuantity']['value'])->toBe(75.5);
    expect($result['valueQuantity']['unit'])->toBe('kg');
});

it('includes valueRange', function () {
    $observation = createObservation([
        'code' => '39149-9',
        'display' => 'Intake 24 hours',
        'value_type' => 'range',
        'value_range_low' => 1000.0,
        'value_range_high' => 3000.0,
    ]);

    $result = $this->transformer->toFhir($observation);
    expect($result['valueRange']['low']['value'])->toBe(1000.0);
    expect($result['valueRange']['high']['value'])->toBe(3000.0);
});

it('includes dataAbsentReason when no value', function () {
    $observation = createObservation([
        'status' => ObservationStatus::REGISTERED,
        'data_absent_reason' => 'not-performed',
    ]);

    $result = $this->transformer->toFhir($observation);

    expect($result['dataAbsentReason']['coding'][0]['code'])->toBe('not-performed');
    expect($result)->not->toHaveKey('valueQuantity');
});

it('includes interpretation from abnormal_flag', function () {
    $observation = createObservation(['abnormal_flag' => AbnormalFlag::LOW]);

    $result = $this->transformer->toFhir($observation);

    expect($result['interpretation'][0]['coding'][0]['code'])->toBe('L');
    expect($result['interpretation'][0]['coding'][0]['system'])->toBe('http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation');
});

it('includes reference range', function () {
    $observation = createObservation([
        'reference_range_min' => 12.0,
        'reference_range_max' => 16.0,
        'reference_range_text' => 'Normal range',
    ]);

    $result = $this->transformer->toFhir($observation);

    expect($result['referenceRange'][0]['low']['value'])->toBe(12.0);
    expect($result['referenceRange'][0]['high']['value'])->toBe(16.0);
    expect($result['referenceRange'][0]['text'])->toBe('Normal range');
});

it('includes performer', function () {
    $user = Mockery::mock('stdClass');
    $user->id = 'user-uuid';
    $user->name = 'Dr. Smith';

    $observation = createObservation([
        'performed_by' => 'user-uuid',
        'performedBy' => $user,
    ]);

    $result = $this->transformer->toFhir($observation);

    expect($result['performer'][0]['reference'])->toBe('Practitioner/user-uuid');
    expect($result['performer'][0]['display'])->toBe('Dr. Smith');
});

it('includes effectiveDateTime and issued', function () {
    $now = Carbon::now();

    $observation = createObservation([
        'performed_at' => $now,
        'verified_at' => $now,
    ]);

    $result = $this->transformer->toFhir($observation);

    expect($result['effectiveDateTime'])->toBe($now->toIso8601String());
    expect($result['issued'])->toBe($now->toIso8601String());
});

it('includes specimen reference', function () {
    $observation = createObservation(['specimen_id' => 'specimen-uuid']);

    $result = $this->transformer->toFhir($observation);
    expect($result['specimen']['reference'])->toBe('Specimen/specimen-uuid');
});

it('includes notes', function () {
    $observation = createObservation(['notes' => 'Result verified']);

    $result = $this->transformer->toFhir($observation);
    expect($result['note'][0]['text'])->toBe('Result verified');
});

it('includes hasMember for child observations', function () {
    $child1 = Mockery::mock(DiagnosticObservation::class);
    $child1->shouldReceive('getAttribute')->with('id')->andReturn('child-1');

    $child2 = Mockery::mock(DiagnosticObservation::class);
    $child2->shouldReceive('getAttribute')->with('id')->andReturn('child-2');

    $observation = createObservation([
        'code' => '58410-2',
        'display' => 'CBC panel',
        'childObservations' => new Collection([$child1, $child2]),
    ]);

    $result = $this->transformer->toFhir($observation);

    expect($result['hasMember'])->toHaveCount(2);
    expect($result['hasMember'][0]['reference'])->toBe('Observation/child-1');
    expect($result['hasMember'][1]['reference'])->toBe('Observation/child-2');
});

it('includes components', function () {
    $component = createComponent([
        'code' => '718-7',
        'display' => 'Hemoglobin',
        'value_type' => 'quantity',
        'value_quantity_value' => 14.5,
        'value_quantity_unit' => 'g/dL',
    ]);

    $observation = createObservation([
        'code' => '58410-2',
        'display' => 'CBC panel',
        'components' => new Collection([$component]),
    ]);

    $result = $this->transformer->toFhir($observation);

    expect($result['component'])->toHaveCount(1);
    expect($result['component'][0]['code']['coding'][0]['code'])->toBe('718-7');
    expect($result['component'][0]['valueQuantity']['value'])->toBe(14.5);
});

it('creates from FHIR resource', function () {
    $result = $this->transformer->fromFhir([
        'resourceType' => 'Observation',
        'status' => 'final',
        'code' => [
            'coding' => [
                ['system' => 'http://loinc.org', 'code' => '718-7', 'display' => 'Hemoglobin'],
            ],
        ],
        'valueQuantity' => [
            'value' => 15.5,
            'unit' => 'g/dL',
        ],
    ]);

    expect($result['status'])->toBe('final');
    expect($result['code'])->toBe('718-7');
    expect($result['display'])->toBe('Hemoglobin');
    expect($result['value_type'])->toBe('quantity');
    expect($result['value_quantity_value'])->toBe(15.5);
    expect($result['value_quantity_unit'])->toBe('g/dL');
});

it('creates from FHIR resource with valueString', function () {
    $result = $this->transformer->fromFhir([
        'resourceType' => 'Observation',
        'status' => 'final',
        'code' => ['text' => 'Urine color'],
        'valueString' => 'Yellow',
    ]);

    expect($result['code'])->toBe('Urine color');
    expect($result['value_type'])->toBe('text');
    expect($result['value_text'])->toBe('Yellow');
});

it('provides searchable parameters', function () {
    $params = $this->transformer->searchableParameters();

    expect($params)->toHaveKeys(['_id', 'status', 'code', 'subject', 'date']);
    expect($params['_id']['column'])->toBe('id');
    expect($params['status']['column'])->toBe('status');
    expect($params['code']['column'])->toBe('code');
});

it('validates business rules - valid status', function () {
    $errors = $this->transformer->validateBusinessRules([
        'resourceType' => 'Observation',
        'status' => 'final',
        'code' => [
            'coding' => [
                ['code' => '718-7'],
            ],
        ],
    ]);

    expect($errors)->toBeEmpty();
});

it('validates business rules - missing status', function () {
    $errors = $this->transformer->validateBusinessRules([
        'resourceType' => 'Observation',
        'code' => ['text' => 'Some code'],
    ]);

    expect($errors)->toHaveKey('obs-1');
});

it('validates business rules - missing code', function () {
    $errors = $this->transformer->validateBusinessRules([
        'resourceType' => 'Observation',
        'status' => 'final',
    ]);

    expect($errors)->toHaveKey('obs-2');
});

it('validates business rules - dataAbsentReason with value', function () {
    $errors = $this->transformer->validateBusinessRules([
        'resourceType' => 'Observation',
        'status' => 'final',
        'code' => ['text' => 'Test'],
        'dataAbsentReason' => ['coding' => [['code' => 'not-performed']]],
        'valueString' => 'present',
    ]);

    expect($errors)->toHaveKey('obs-6');
});
