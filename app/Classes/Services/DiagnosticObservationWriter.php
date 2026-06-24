<?php

namespace Modules\Diagnostics\Classes\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\ObservationStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;
use Modules\Diagnostics\Models\DiagnosticReportVersion;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;
use Modules\Diagnostics\Models\DiagnosticSpecimen;
use Modules\Patient\Models\Patient;

class DiagnosticObservationWriter
{
    /**
     * @return Collection<int, DiagnosticObservation>
     */
    public function persistResults(
        DiagnosticFulfillment $fulfillment,
        DiagnosticServiceProfile $profile,
        array $formData,
        ?DiagnosticReportVersion $reportVersion = null,
        ?DiagnosticSpecimen $specimen = null,
        ?User $performedBy = null,
    ): Collection {
        $fulfillment->loadMissing('requestItem.serviceRequest.patient');
        $profile->loadMissing([
            'panel.items.childProfile.service',
            'panel.items.childProfile.referenceRanges',
            'referenceRanges',
            'defaultTemplate.fields',
            'templates.fields',
        ]);

        $patient = $fulfillment->requestItem?->serviceRequest?->patient;

        $observations = $profile->panel
            ? $this->persistPanelResults($fulfillment, $profile, $formData, $patient, $specimen, $performedBy)
            : $this->persistNonPanelResults($fulfillment, $profile, $formData, $patient, $specimen, $performedBy);

        if ($reportVersion !== null) {
            $this->linkToReportVersion($reportVersion, $observations);
        }

        return $observations;
    }

    /**
     * @return Collection<int, DiagnosticObservation>
     */
    protected function persistPanelResults(
        DiagnosticFulfillment $fulfillment,
        DiagnosticServiceProfile $profile,
        array $formData,
        ?Patient $patient,
        ?DiagnosticSpecimen $specimen,
        ?User $performedBy,
    ): Collection {
        $submitted = $this->extractSubmittedValues($formData, $profile);
        $observations = collect();

        foreach ($profile->panel->items as $item) {
            $childProfile = $item->childProfile;

            if ($childProfile === null) {
                continue;
            }

            $code = $this->resolveObservationCode($childProfile);
            $value = $this->findSubmittedValue($submitted, $code, $childProfile);
            $field = $this->resolveTemplateFieldForProfile($childProfile, $code);

            $observations->push($this->upsertObservation(
                fulfillment: $fulfillment,
                code: $code,
                display: $childProfile->loinc_display ?? $childProfile->service?->name ?? $code,
                profileId: $childProfile->id,
                specimenId: $specimen?->id,
                value: $value,
                valueProfile: $childProfile,
                field: $field,
                patient: $patient,
                performedBy: $performedBy,
                sortOrder: $item->sequence,
            ));
        }

        return $observations;
    }

    /**
     * @return Collection<int, DiagnosticObservation>
     */
    protected function persistNonPanelResults(
        DiagnosticFulfillment $fulfillment,
        DiagnosticServiceProfile $profile,
        array $formData,
        ?Patient $patient,
        ?DiagnosticSpecimen $specimen,
        ?User $performedBy,
    ): Collection {
        $templateFields = $this->getTemplateFields($profile);

        if ($templateFields->isNotEmpty()) {
            return $this->persistTemplateResults(
                $fulfillment,
                $profile,
                $templateFields,
                $formData,
                $patient,
                $specimen,
                $performedBy,
            );
        }

        return $this->persistRepeaterResults(
            $fulfillment,
            $profile,
            $formData,
            $patient,
            $specimen,
            $performedBy,
        );
    }

    /**
     * @param  Collection<int, DiagnosticResultTemplateField>  $templateFields
     * @return Collection<int, DiagnosticObservation>
     */
    protected function persistTemplateResults(
        DiagnosticFulfillment $fulfillment,
        DiagnosticServiceProfile $profile,
        Collection $templateFields,
        array $formData,
        ?Patient $patient,
        ?DiagnosticSpecimen $specimen,
        ?User $performedBy,
    ): Collection {
        $observations = collect();

        foreach ($templateFields as $field) {
            $name = "field_{$field->field_key}";

            if (! array_key_exists($name, $formData)) {
                continue;
            }

            $code = $field->observation_code ?? $field->field_key;

            $observations->push($this->upsertObservation(
                fulfillment: $fulfillment,
                code: $code,
                display: $field->observation_name ?? $field->label,
                profileId: $profile->id,
                specimenId: $specimen?->id,
                value: $formData[$name],
                valueProfile: $profile,
                field: $field,
                patient: $patient,
                performedBy: $performedBy,
                sortOrder: $field->sort_order ?? 0,
            ));
        }

        return $observations;
    }

    /**
     * @return Collection<int, DiagnosticObservation>
     */
    protected function persistRepeaterResults(
        DiagnosticFulfillment $fulfillment,
        DiagnosticServiceProfile $profile,
        array $formData,
        ?Patient $patient,
        ?DiagnosticSpecimen $specimen,
        ?User $performedBy,
    ): Collection {
        $observations = collect();

        foreach ((array) ($formData['results'] ?? []) as $index => $row) {
            if (empty($row['key'])) {
                continue;
            }

            $code = (string) $row['key'];

            $observations->push($this->upsertObservation(
                fulfillment: $fulfillment,
                code: $code,
                display: ucfirst(str_replace('_', ' ', $code)),
                profileId: $profile->id,
                specimenId: $specimen?->id,
                value: $row['value'] ?? null,
                valueProfile: $profile,
                field: null,
                patient: $patient,
                performedBy: $performedBy,
                sortOrder: $index,
            ));
        }

        return $observations;
    }

    protected function upsertObservation(
        DiagnosticFulfillment $fulfillment,
        string $code,
        string $display,
        string $profileId,
        ?string $specimenId,
        mixed $value,
        DiagnosticServiceProfile $valueProfile,
        ?DiagnosticResultTemplateField $field,
        ?Patient $patient,
        ?User $performedBy,
        int $sortOrder,
    ): DiagnosticObservation {
        $range = $this->resolveReferenceRange($valueProfile, $field, $patient);
        $typed = $this->parseValue($value, $field);
        $flag = $this->computeAbnormalFlag($typed['numeric'], $typed['text'], $range);
        $hasValue = $typed['numeric'] !== null
            || $typed['text'] !== null
            || $typed['coded'] !== null
            || $typed['boolean'] !== null;

        return $fulfillment->observations()->updateOrCreate(
            ['code' => $code],
            [
                'branch_id' => $fulfillment->branch_id,
                'profile_id' => $profileId,
                'specimen_id' => $specimenId,
                'display' => $display,
                'status' => $hasValue ? ObservationStatus::FINAL : ObservationStatus::REGISTERED,
                'value_type' => $typed['type'],
                'value_numeric' => $typed['numeric'],
                'value_text' => $typed['text'],
                'value_coded' => $typed['coded'],
                'value_boolean' => $typed['boolean'],
                'units' => $range['units'] ?? $field?->default_units,
                'reference_range_min' => $range['min'],
                'reference_range_max' => $range['max'],
                'reference_range_text' => $range['text'],
                'abnormal_flag' => $flag,
                'interpretation' => $this->computeInterpretation($flag),
                'performed_by' => $hasValue ? $performedBy?->id : null,
                'performed_at' => $hasValue ? now() : null,
                'sort_order' => $sortOrder,
            ],
        );
    }

    /**
     * @param  Collection<int, DiagnosticObservation>  $observations
     */
    protected function linkToReportVersion(DiagnosticReportVersion $reportVersion, Collection $observations): void
    {
        $sync = [];

        foreach ($observations as $observation) {
            $sync[$observation->id] = ['sort_order' => $observation->sort_order ?? 0];
        }

        if ($sync !== []) {
            $reportVersion->observations()->syncWithoutDetaching($sync);
        }
    }

    /**
     * @return array{min: ?float, max: ?float, text: ?string, units: ?string, critical_low: ?float, critical_high: ?float}
     */
    protected function resolveReferenceRange(
        DiagnosticServiceProfile $profile,
        ?DiagnosticResultTemplateField $field,
        ?Patient $patient,
    ): array {
        if ($patient !== null) {
            $ageMonths = $patient->date_of_birth?->diffInMonths(now());
            $gender = $patient->gender?->value;

            $matched = $profile->referenceRanges->first(function ($range) use ($ageMonths, $gender) {
                if ($range->gender !== 'any' && $range->gender !== $gender) {
                    return false;
                }

                if ($range->age_min_months !== null && $ageMonths !== null && $ageMonths < $range->age_min_months) {
                    return false;
                }

                if ($range->age_max_months !== null && $ageMonths !== null && $ageMonths > $range->age_max_months) {
                    return false;
                }

                return true;
            });

            if ($matched !== null) {
                return [
                    'min' => $matched->min_value !== null ? (float) $matched->min_value : null,
                    'max' => $matched->max_value !== null ? (float) $matched->max_value : null,
                    'text' => $matched->range_text,
                    'units' => $matched->units,
                    'critical_low' => $matched->critical_low !== null ? (float) $matched->critical_low : null,
                    'critical_high' => $matched->critical_high !== null ? (float) $matched->critical_high : null,
                ];
            }
        }

        if ($field !== null && ($field->reference_range_low !== null || $field->reference_range_high !== null)) {
            return [
                'min' => $field->reference_range_low !== null ? (float) $field->reference_range_low : null,
                'max' => $field->reference_range_high !== null ? (float) $field->reference_range_high : null,
                'text' => null,
                'units' => $field->default_units,
                'critical_low' => null,
                'critical_high' => null,
            ];
        }

        return [
            'min' => null,
            'max' => null,
            'text' => null,
            'units' => $field?->default_units,
            'critical_low' => null,
            'critical_high' => null,
        ];
    }

    /**
     * @return array{type: ?string, numeric: ?float, text: ?string, coded: ?string, boolean: ?bool}
     */
    protected function parseValue(mixed $value, ?DiagnosticResultTemplateField $field): array
    {
        if ($value === null || $value === '') {
            return [
                'type' => $field?->value_type ?? $field?->data_type,
                'numeric' => null,
                'text' => null,
                'coded' => null,
                'boolean' => null,
            ];
        }

        $valueType = $field?->value_type ?? $field?->data_type ?? 'text';

        if ($valueType === 'numeric' && is_numeric($value)) {
            return [
                'type' => 'numeric',
                'numeric' => (float) $value,
                'text' => null,
                'coded' => null,
                'boolean' => null,
            ];
        }

        if ($valueType === 'select') {
            return [
                'type' => 'coded',
                'numeric' => null,
                'text' => (string) $value,
                'coded' => (string) $value,
                'boolean' => null,
            ];
        }

        if (is_bool($value)) {
            return [
                'type' => 'boolean',
                'numeric' => null,
                'text' => null,
                'coded' => null,
                'boolean' => $value,
            ];
        }

        if (is_numeric($value)) {
            return [
                'type' => 'numeric',
                'numeric' => (float) $value,
                'text' => null,
                'coded' => null,
                'boolean' => null,
            ];
        }

        return [
            'type' => 'text',
            'numeric' => null,
            'text' => (string) $value,
            'coded' => null,
            'boolean' => null,
        ];
    }

    /**
     * @param  array{min: ?float, max: ?float, text: ?string, units: ?string, critical_low: ?float, critical_high: ?float}  $range
     */
    protected function computeAbnormalFlag(?float $numericValue, ?string $textValue, array $range): ?AbnormalFlag
    {
        if ($numericValue !== null) {
            if ($range['critical_low'] !== null && $numericValue < $range['critical_low']) {
                return AbnormalFlag::CRITICALLY_LOW;
            }

            if ($range['critical_high'] !== null && $numericValue > $range['critical_high']) {
                return AbnormalFlag::CRITICALLY_HIGH;
            }

            if ($range['min'] !== null && $numericValue < $range['min']) {
                return AbnormalFlag::LOW;
            }

            if ($range['max'] !== null && $numericValue > $range['max']) {
                return AbnormalFlag::HIGH;
            }

            if ($range['min'] !== null || $range['max'] !== null) {
                return AbnormalFlag::NORMAL;
            }

            return null;
        }

        if ($textValue !== null) {
            $normalized = strtolower(trim($textValue));

            return match ($normalized) {
                'positive', 'reactive', 'detected' => AbnormalFlag::POSITIVE,
                'negative', 'non-reactive', 'not detected' => AbnormalFlag::NEGATIVE,
                default => null,
            };
        }

        return null;
    }

    protected function computeInterpretation(?AbnormalFlag $flag): ?string
    {
        return match ($flag) {
            AbnormalFlag::NORMAL => 'Within normal limits',
            AbnormalFlag::HIGH => 'Above reference range',
            AbnormalFlag::LOW => 'Below reference range',
            AbnormalFlag::CRITICALLY_HIGH => 'Critically high',
            AbnormalFlag::CRITICALLY_LOW => 'Critically low',
            AbnormalFlag::POSITIVE => 'Positive',
            AbnormalFlag::NEGATIVE => 'Negative',
            AbnormalFlag::ABNORMAL => 'Abnormal',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function extractSubmittedValues(array $formData, DiagnosticServiceProfile $profile): array
    {
        $submitted = [];

        foreach ($this->getTemplateFields($profile) as $field) {
            $name = "field_{$field->field_key}";

            if (array_key_exists($name, $formData)) {
                $submitted[$field->field_key] = $formData[$name];
                $submitted[$field->observation_code ?? $field->field_key] = $formData[$name];
            }
        }

        foreach ((array) ($formData['results'] ?? []) as $row) {
            if (! empty($row['key'])) {
                $submitted[(string) $row['key']] = $row['value'] ?? null;
            }
        }

        return $submitted;
    }

    protected function findSubmittedValue(array $submitted, string $code, DiagnosticServiceProfile $childProfile): mixed
    {
        if (array_key_exists($code, $submitted)) {
            return $submitted[$code];
        }

        $serviceKey = str($childProfile->service?->name ?? '')->snake()->toString();

        if ($serviceKey !== '' && array_key_exists($serviceKey, $submitted)) {
            return $submitted[$serviceKey];
        }

        $field = $this->resolveTemplateFieldForProfile($childProfile, $code);

        if ($field !== null && array_key_exists($field->field_key, $submitted)) {
            return $submitted[$field->field_key];
        }

        return null;
    }

    protected function resolveTemplateFieldForProfile(
        DiagnosticServiceProfile $profile,
        string $code,
    ): ?DiagnosticResultTemplateField {
        return $this->getTemplateFields($profile)->first(function (DiagnosticResultTemplateField $field) use ($code) {
            return $field->field_key === $code
                || $field->observation_code === $code;
        });
    }

    protected function resolveObservationCode(DiagnosticServiceProfile $profile): string
    {
        if ($profile->loinc_code) {
            return $profile->loinc_code;
        }

        if ($profile->service?->name) {
            return str($profile->service->name)->snake()->toString();
        }

        return $profile->id;
    }

    /**
     * @return Collection<int, DiagnosticResultTemplateField>
     */
    protected function getTemplateFields(DiagnosticServiceProfile $profile): Collection
    {
        $template = $profile->defaultTemplate;

        if ($template === null) {
            $template = $profile->templates()->where('is_active', true)->first();
        }

        return $template?->fields ?? collect();
    }
}
