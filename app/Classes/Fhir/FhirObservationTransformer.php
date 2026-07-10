<?php

namespace Modules\Diagnostics\Classes\Fhir;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\ObservationStatus;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticObservationComponent;
use Modules\FHIR\Contracts\FhirResourceContract;

class FhirObservationTransformer implements FhirResourceContract
{
    private const CATEGORY_SYSTEM = 'http://terminology.hl7.org/CodeSystem/observation-category';

    private const LOINC_SYSTEM = 'http://loinc.org';

    private const UCUM_SYSTEM = 'http://unitsofmeasure.org';

    private const INTERPRETATION_SYSTEM = 'http://terminology.hl7.org/CodeSystem/v3-ObservationInterpretation';

    private const INTERPRETATION_MAP = [
        AbnormalFlag::NORMAL->value => 'N',
        AbnormalFlag::HIGH->value => 'H',
        AbnormalFlag::LOW->value => 'L',
        AbnormalFlag::CRITICALLY_HIGH->value => 'HH',
        AbnormalFlag::CRITICALLY_LOW->value => 'LL',
        AbnormalFlag::ABNORMAL->value => 'A',
        AbnormalFlag::POSITIVE->value => 'POS',
        AbnormalFlag::NEGATIVE->value => 'NEG',
    ];

    public function resourceType(): string
    {
        return 'Observation';
    }

    public function toFhir(Model $model): array
    {
        $resource = [
            'resourceType' => 'Observation',
            'id' => $model->id,
            'status' => $model->status->value,
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => self::CATEGORY_SYSTEM,
                            'code' => 'laboratory',
                            'display' => 'Laboratory',
                        ],
                    ],
                ],
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => self::LOINC_SYSTEM,
                        'code' => $model->code,
                        'display' => $model->display,
                    ],
                ],
                'text' => $model->display,
            ],
        ];

        $subject = $this->resolveSubject($model);
        if ($subject) {
            $resource['subject'] = $subject;
        }

        $encounter = $this->resolveEncounter($model);
        if ($encounter) {
            $resource['encounter'] = $encounter;
        }

        $basedOn = $this->resolveBasedOn($model);
        if ($basedOn) {
            $resource['basedOn'] = $basedOn;
        }

        if ($model->performed_at) {
            $resource['effectiveDateTime'] = $model->performed_at instanceof DateTime
                ? $model->performed_at->toIso8601String()
                : Carbon::parse($model->performed_at)->toIso8601String();
        }

        if ($model->verified_at) {
            $resource['issued'] = $model->verified_at instanceof DateTime
                ? $model->verified_at->toIso8601String()
                : Carbon::parse($model->verified_at)->toIso8601String();
        }

        $performer = $this->buildPerformer($model);
        if ($performer) {
            $resource['performer'] = [$performer];
        }

        $value = $this->buildValue($model);
        if ($value !== null) {
            $resource[key($value)] = $value[key($value)];
        }

        if ($model->data_absent_reason && $value === null) {
            $resource['dataAbsentReason'] = [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/data-absent-reason',
                        'code' => $model->data_absent_reason,
                    ],
                ],
            ];
        }

        $interpretation = $this->buildInterpretation($model);
        if ($interpretation) {
            $resource['interpretation'] = $interpretation;
        }

        if ($model->notes) {
            $resource['note'] = [
                ['text' => $model->notes],
            ];
        }

        if ($model->specimen_id) {
            $resource['specimen'] = [
                'reference' => "Specimen/{$model->specimen_id}",
            ];
        }

        $referenceRange = $this->buildReferenceRange($model);
        if ($referenceRange) {
            $resource['referenceRange'] = $referenceRange;
        }

        $hasMember = $this->buildHasMember($model);
        if ($hasMember) {
            $resource['hasMember'] = $hasMember;
        }

        $components = $this->buildComponents($model);
        if ($components) {
            $resource['component'] = $components;
        }

        return $resource;
    }

    private function resolveSubject(DiagnosticObservation $model): ?array
    {
        $patientId = $model->fulfillment?->requestItem?->serviceRequest?->patient_id;

        if (! $patientId) {
            return null;
        }

        return ['reference' => "Patient/{$patientId}"];
    }

    private function resolveEncounter(DiagnosticObservation $model): ?array
    {
        $encounterId = $model->fulfillment?->requestItem?->serviceRequest?->encounter_id;

        if (! $encounterId) {
            return null;
        }

        return ['reference' => "Encounter/{$encounterId}"];
    }

    private function resolveBasedOn(DiagnosticObservation $model): ?array
    {
        $requestItem = $model->fulfillment?->requestItem;

        if (! $requestItem) {
            return null;
        }

        $serviceRequest = $requestItem->serviceRequest;

        if (! $serviceRequest) {
            return null;
        }

        return [
            ['reference' => "ServiceRequest/{$serviceRequest->id}"],
        ];
    }

    private function buildPerformer(DiagnosticObservation $model): ?array
    {
        $user = $model->performedBy;

        if (! $user) {
            return null;
        }

        return [
            'reference' => "Practitioner/{$user->id}",
            'display' => $user->name,
        ];
    }

    private function buildValue(DiagnosticObservation $model): ?array
    {
        return match ($model->value_type) {
            'quantity' => $model->value_quantity_value !== null ? [
                'valueQuantity' => [
                    'value' => (float) $model->value_quantity_value,
                    'unit' => $model->value_quantity_unit,
                    'system' => self::UCUM_SYSTEM,
                    'code' => $model->value_quantity_unit,
                ],
            ] : null,
            'numeric' => $model->value_numeric !== null ? (
                $model->units
                    ? ['valueQuantity' => [
                        'value' => (float) $model->value_numeric,
                        'unit' => $model->units,
                        'system' => self::UCUM_SYSTEM,
                        'code' => $model->units,
                    ]]
                    : ['valueInteger' => (int) $model->value_numeric]
            ) : null,
            'text' => $model->value_text !== null ? [
                'valueString' => $model->value_text,
            ] : null,
            'coded' => $model->value_coded !== null ? [
                'valueCodeableConcept' => [
                    'coding' => [
                        [
                            'code' => $model->value_coded,
                            'display' => $model->value_text ?? $model->display,
                        ],
                    ],
                    'text' => $model->value_text ?? $model->display,
                ],
            ] : null,
            'boolean' => $model->value_boolean !== null ? [
                'valueBoolean' => (bool) $model->value_boolean,
            ] : null,
            'range' => $model->value_range_low !== null || $model->value_range_high !== null ? [
                'valueRange' => [
                    'low' => $model->value_range_low !== null
                        ? ['value' => (float) $model->value_range_low]
                        : null,
                    'high' => $model->value_range_high !== null
                        ? ['value' => (float) $model->value_range_high]
                        : null,
                ],
            ] : null,
            default => null,
        };
    }

    private function buildInterpretation(DiagnosticObservation $model): ?array
    {
        if (! $model->abnormal_flag) {
            return null;
        }

        $interpretationCode = self::INTERPRETATION_MAP[$model->abnormal_flag->value] ?? null;

        if (! $interpretationCode) {
            return null;
        }

        return [
            [
                'coding' => [
                    [
                        'system' => self::INTERPRETATION_SYSTEM,
                        'code' => $interpretationCode,
                        'display' => $model->abnormal_flag->getLabel(),
                    ],
                ],
            ],
        ];
    }

    private function buildReferenceRange(DiagnosticObservation $model): ?array
    {
        if ($model->reference_range_min === null && $model->reference_range_max === null && ! $model->reference_range_text) {
            return null;
        }

        $range = [];

        if ($model->reference_range_min !== null) {
            $range['low'] = ['value' => (float) $model->reference_range_min];
        }

        if ($model->reference_range_max !== null) {
            $range['high'] = ['value' => (float) $model->reference_range_max];
        }

        if ($model->reference_range_text) {
            $range['text'] = $model->reference_range_text;
        }

        return [$range];
    }

    private function buildHasMember(DiagnosticObservation $model): ?array
    {
        $children = $model->childObservations;

        if ($children->isEmpty()) {
            return null;
        }

        return $children->map(fn (DiagnosticObservation $child) => [
            'reference' => "Observation/{$child->id}",
        ])->values()->all();
    }

    private function buildComponents(DiagnosticObservation $model): ?array
    {
        $components = $model->components;

        if ($components->isEmpty()) {
            return null;
        }

        return $components->map(function (DiagnosticObservationComponent $component) {
            $entry = [
                'code' => [
                    'coding' => [
                        [
                            'system' => self::LOINC_SYSTEM,
                            'code' => $component->code,
                            'display' => $component->display,
                        ],
                    ],
                    'text' => $component->display,
                ],
            ];

            $value = $this->buildComponentValue($component);
            if ($value !== null) {
                $entry[key($value)] = $value[key($value)];
            }

            if ($component->data_absent_reason && $value === null) {
                $entry['dataAbsentReason'] = [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/data-absent-reason',
                            'code' => $component->data_absent_reason,
                        ],
                    ],
                ];
            }

            if ($component->abnormal_flag) {
                $interpretationCode = self::INTERPRETATION_MAP[$component->abnormal_flag->value] ?? null;
                if ($interpretationCode) {
                    $entry['interpretation'] = [
                        [
                            'coding' => [
                                [
                                    'system' => self::INTERPRETATION_SYSTEM,
                                    'code' => $interpretationCode,
                                    'display' => $component->abnormal_flag->getLabel(),
                                ],
                            ],
                        ],
                    ];
                }
            }

            $refRange = $this->buildComponentReferenceRange($component);
            if ($refRange) {
                $entry['referenceRange'] = $refRange;
            }

            return $entry;
        })->values()->all();
    }

    private function buildComponentValue(DiagnosticObservationComponent $component): ?array
    {
        return match ($component->value_type) {
            'quantity' => $component->value_quantity_value !== null ? [
                'valueQuantity' => [
                    'value' => (float) $component->value_quantity_value,
                    'unit' => $component->value_quantity_unit,
                    'system' => self::UCUM_SYSTEM,
                    'code' => $component->value_quantity_unit,
                ],
            ] : null,
            'numeric' => $component->value_numeric !== null ? (
                $component->units
                    ? ['valueQuantity' => [
                        'value' => (float) $component->value_numeric,
                        'unit' => $component->units,
                        'system' => self::UCUM_SYSTEM,
                        'code' => $component->units,
                    ]]
                    : ['valueInteger' => (int) $component->value_numeric]
            ) : null,
            'text' => $component->value_text !== null ? [
                'valueString' => $component->value_text,
            ] : null,
            'coded' => $component->value_coded !== null ? [
                'valueCodeableConcept' => [
                    'coding' => [
                        [
                            'code' => $component->value_coded,
                            'display' => $component->value_text ?? $component->display,
                        ],
                    ],
                    'text' => $component->value_text ?? $component->display,
                ],
            ] : null,
            'boolean' => $component->value_boolean !== null ? [
                'valueBoolean' => (bool) $component->value_boolean,
            ] : null,
            'range' => $component->value_range_low !== null || $component->value_range_high !== null ? [
                'valueRange' => [
                    'low' => $component->value_range_low !== null
                        ? ['value' => (float) $component->value_range_low]
                        : null,
                    'high' => $component->value_range_high !== null
                        ? ['value' => (float) $component->value_range_high]
                        : null,
                ],
            ] : null,
            default => null,
        };
    }

    private function buildComponentReferenceRange(DiagnosticObservationComponent $component): ?array
    {
        if ($component->reference_range_min === null && $component->reference_range_max === null && ! $component->reference_range_text) {
            return null;
        }

        $range = [];

        if ($component->reference_range_min !== null) {
            $range['low'] = ['value' => (float) $component->reference_range_min];
        }

        if ($component->reference_range_max !== null) {
            $range['high'] = ['value' => (float) $component->reference_range_max];
        }

        if ($component->reference_range_text) {
            $range['text'] = $component->reference_range_text;
        }

        return [$range];
    }

    public function fromFhir(array $fhirResource): array
    {
        $attrs = [];

        if (isset($fhirResource['status'])) {
            $validStatuses = ObservationStatus::values();
            $attrs['status'] = in_array($fhirResource['status'], $validStatuses, true)
                ? $fhirResource['status']
                : null;
        }

        if (isset($fhirResource['code']['coding'][0]['code'])) {
            $attrs['code'] = $fhirResource['code']['coding'][0]['code'];
            $attrs['display'] = $fhirResource['code']['coding'][0]['display']
                ?? $fhirResource['code']['text']
                ?? null;
        } elseif (isset($fhirResource['code']['text'])) {
            $attrs['code'] = $fhirResource['code']['text'];
        }

        if (isset($fhirResource['valueQuantity'])) {
            $attrs['value_type'] = 'quantity';
            $attrs['value_quantity_value'] = $fhirResource['valueQuantity']['value'] ?? null;
            $attrs['value_quantity_unit'] = $fhirResource['valueQuantity']['unit'] ?? $fhirResource['valueQuantity']['code'] ?? null;
        } elseif (isset($fhirResource['valueInteger'])) {
            $attrs['value_type'] = 'numeric';
            $attrs['value_numeric'] = $fhirResource['valueInteger'];
        } elseif (isset($fhirResource['valueString'])) {
            $attrs['value_type'] = 'text';
            $attrs['value_text'] = $fhirResource['valueString'];
        } elseif (isset($fhirResource['valueCodeableConcept'])) {
            $attrs['value_type'] = 'coded';
            if (isset($fhirResource['valueCodeableConcept']['coding'][0]['code'])) {
                $attrs['value_coded'] = $fhirResource['valueCodeableConcept']['coding'][0]['code'];
            }
            $attrs['value_text'] = $fhirResource['valueCodeableConcept']['text'] ?? null;
        } elseif (isset($fhirResource['valueBoolean'])) {
            $attrs['value_type'] = 'boolean';
            $attrs['value_boolean'] = $fhirResource['valueBoolean'];
        } elseif (isset($fhirResource['valueRange'])) {
            $attrs['value_type'] = 'range';
            $attrs['value_range_low'] = $fhirResource['valueRange']['low']['value'] ?? null;
            $attrs['value_range_high'] = $fhirResource['valueRange']['high']['value'] ?? null;
        }

        if (isset($fhirResource['dataAbsentReason']['coding'][0]['code'])) {
            $attrs['data_absent_reason'] = $fhirResource['dataAbsentReason']['coding'][0]['code'];
        }

        if (isset($fhirResource['effectiveDateTime'])) {
            $attrs['performed_at'] = $fhirResource['effectiveDateTime'];
        }

        if (isset($fhirResource['issued'])) {
            $attrs['verified_at'] = $fhirResource['issued'];
        }

        if (isset($fhirResource['note'][0]['text'])) {
            $attrs['notes'] = $fhirResource['note'][0]['text'];
        }

        if (isset($fhirResource['referenceRange'][0]['low']['value'])) {
            $attrs['reference_range_min'] = $fhirResource['referenceRange'][0]['low']['value'];
        }

        if (isset($fhirResource['referenceRange'][0]['high']['value'])) {
            $attrs['reference_range_max'] = $fhirResource['referenceRange'][0]['high']['value'];
        }

        if (isset($fhirResource['referenceRange'][0]['text'])) {
            $attrs['reference_range_text'] = $fhirResource['referenceRange'][0]['text'];
        }

        $reverseInterpretationMap = array_flip(self::INTERPRETATION_MAP);
        if (isset($fhirResource['interpretation'][0]['coding'][0]['code'])) {
            $fhirCode = $fhirResource['interpretation'][0]['coding'][0]['code'];
            $attrs['abnormal_flag'] = $reverseInterpretationMap[$fhirCode] ?? null;
        }

        if (isset($fhirResource['subject']['reference'])) {
            $parts = explode('/', $fhirResource['subject']['reference']);
            $attrs['subject_reference'] = end($parts);
        }

        return $attrs;
    }

    public function findById(string $id): ?Model
    {
        return DiagnosticObservation::with([
            'fulfillment.requestItem.serviceRequest',
            'components',
            'specimen',
            'performedBy',
            'childObservations',
        ])->find($id);
    }

    public function query(): Builder
    {
        return DiagnosticObservation::with([
            'fulfillment.requestItem.serviceRequest',
            'components',
            'specimen',
            'performedBy',
            'childObservations',
        ]);
    }

    public function searchableParameters(): array
    {
        return [
            '_id' => ['column' => 'id'],
            'status' => ['column' => 'status'],
            'code' => ['column' => 'code'],
            'subject' => ['column' => 'fulfillment_id'],
            'encounter' => ['column' => 'fulfillment_id'],
            'patient' => ['column' => 'fulfillment_id'],
            'date' => ['column' => 'performed_at'],
            'performer' => ['column' => 'performed_by'],
            'specimen' => ['column' => 'specimen_id'],
            'identifier' => ['column' => 'id'],
        ];
    }

    public function validateBusinessRules(array $fhirResource): array
    {
        $errors = [];

        $validStatuses = ['registered', 'preliminary', 'final', 'amended', 'corrected', 'cancelled', 'entered-in-error', 'unknown'];
        if (! isset($fhirResource['status']) || ! in_array($fhirResource['status'], $validStatuses, true)) {
            $errors['obs-1'] = 'Observation SHALL have a valid status.';
        }

        if (! isset($fhirResource['code']['coding'][0]['code']) && ! isset($fhirResource['code']['text'])) {
            $errors['obs-2'] = 'Observation SHALL have a code (LOINC).';
        }

        if (isset($fhirResource['dataAbsentReason']) && isset($fhirResource['valueQuantity'])) {
            $errors['obs-6'] = 'dataAbsentReason SHALL only be present if value[x] is not present.';
        } elseif (isset($fhirResource['dataAbsentReason']) && isset($fhirResource['valueString'])) {
            $errors['obs-6'] = 'dataAbsentReason SHALL only be present if value[x] is not present.';
        } elseif (isset($fhirResource['dataAbsentReason']) && isset($fhirResource['valueCodeableConcept'])) {
            $errors['obs-6'] = 'dataAbsentReason SHALL only be present if value[x] is not present.';
        } elseif (isset($fhirResource['dataAbsentReason']) && isset($fhirResource['valueBoolean'])) {
            $errors['obs-6'] = 'dataAbsentReason SHALL only be present if value[x] is not present.';
        } elseif (isset($fhirResource['dataAbsentReason']) && isset($fhirResource['valueInteger'])) {
            $errors['obs-6'] = 'dataAbsentReason SHALL only be present if value[x] is not present.';
        } elseif (isset($fhirResource['dataAbsentReason']) && isset($fhirResource['valueRange'])) {
            $errors['obs-6'] = 'dataAbsentReason SHALL only be present if value[x] is not present.';
        }

        return $errors;
    }
}
