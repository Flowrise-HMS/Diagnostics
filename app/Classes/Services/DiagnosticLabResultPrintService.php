<?php

namespace Modules\Diagnostics\Classes\Services;

use Illuminate\Support\Collection;
use Modules\Clinical\Enums\TaskStatus;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Clinical\Models\Task;
use Modules\Core\Support\ClientIdentity;
use Modules\Core\Support\ClientIdentityResolver;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticObservation;

class DiagnosticLabResultPrintService
{
    public function canPrint(DiagnosticFulfillment $fulfillment): bool
    {
        if ($fulfillment->discipline !== DiagnosticDiscipline::LAB) {
            return false;
        }

        if ($fulfillment->status !== FulfillmentStatus::COMPLETED) {
            return false;
        }

        return $this->resolveResultRows($fulfillment)->isNotEmpty();
    }

    /**
     * @return array<string, mixed>
     */
    public function build(DiagnosticFulfillment $fulfillment): array
    {
        $fulfillment->loadMissing([
            'branch.organization',
            'requestItem.service',
            'requestItem.serviceRequest.patient',
            'requestItem.serviceRequest.orderedBy',
            'requestItem.tasks.performedBy',
            'latestReportVersion.observations',
            'latestReportVersion.signatures.signedBy',
        ]);

        $serviceRequest = $fulfillment->requestItem?->serviceRequest;
        $latestTask = $this->resolveLatestCompletedTask($fulfillment);
        $reportVersion = $fulfillment->latestReportVersion;

        return [
            'fulfillment' => $fulfillment,
            'branch' => $fulfillment->branch,
            'organization' => $fulfillment->branch?->organization,
            'serviceRequest' => $serviceRequest,
            'serviceName' => $fulfillment->requestItem?->service?->name ?? 'Laboratory Test',
            'requestNumber' => $serviceRequest?->request_number,
            'subject' => $serviceRequest ? $this->resolveSubject($serviceRequest) : [],
            'client' => $serviceRequest?->clientIdentity() ?? ClientIdentityResolver::resolve(),
            'resultRows' => $this->resolveResultRows($fulfillment),
            'notes' => $latestTask?->notes,
            'performedBy' => $latestTask?->performedBy?->name,
            'collectedAt' => $latestTask?->started_at,
            'reportedAt' => $latestTask?->completed_at ?? $reportVersion?->created_at,
            'reportVersion' => $reportVersion?->version,
            'reportStatus' => $reportVersion?->status,
            'signatures' => $reportVersion?->signatures ?? collect(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveSubject(ServiceRequest $serviceRequest): array
    {
        $client = $serviceRequest->clientIdentity();

        return $this->subjectArrayFromClient($client, $serviceRequest);
    }

    /**
     * @return array<string, mixed>
     */
    protected function subjectArrayFromClient(ClientIdentity $client, ServiceRequest $serviceRequest): array
    {
        if ($client->isPatient()) {
            $patient = $serviceRequest->patient;

            return [
                'type' => 'patient',
                'name' => $client->name,
                'identifier_label' => $client->identifierLabel,
                'identifier' => $client->identifier,
                'age' => $patient?->age,
                'gender' => $patient?->gender?->getLabel(),
                'phone' => $patient?->phone,
            ];
        }

        if ($client->isGuest()) {
            return [
                'type' => 'guest',
                'name' => $client->name,
                'identifier_label' => $client->identifierLabel,
                'identifier' => $client->identifier,
                'age' => null,
                'gender' => null,
                'phone' => $client->phone,
                'email' => $client->email,
            ];
        }

        return [
            'type' => 'unknown',
            'name' => $client->name,
            'identifier_label' => null,
            'identifier' => null,
        ];
    }

    protected function resolveResultRows(DiagnosticFulfillment $fulfillment): Collection
    {
        $observations = $fulfillment->latestReportVersion?->observations;

        if ($observations !== null && $observations->isNotEmpty()) {
            return $observations->map(fn (DiagnosticObservation $observation): array => [
                'label' => $observation->display ?? ucfirst(str_replace('_', ' ', $observation->code)),
                'value' => $this->formatObservationValue($observation),
                'reference' => $this->formatReferenceRange($observation),
            ]);
        }

        $task = $this->resolveLatestCompletedTask($fulfillment);

        if ($task === null || empty($task->results)) {
            return collect();
        }

        return collect($task->results)->map(function (mixed $entry, string|int $key): array {
            if (is_array($entry)) {
                return [
                    'label' => $entry['label'] ?? (is_string($key) ? ucfirst(str_replace('_', ' ', $key)) : 'Result'),
                    'value' => $entry['value'] ?? '',
                    'reference' => $entry['reference'] ?? null,
                ];
            }

            return [
                'label' => is_string($key) ? ucfirst(str_replace('_', ' ', $key)) : 'Result',
                'value' => (string) $entry,
                'reference' => null,
            ];
        })->values();
    }

    protected function formatObservationValue(DiagnosticObservation $observation): string
    {
        if ($observation->value_numeric !== null) {
            $formatted = rtrim(rtrim(number_format((float) $observation->value_numeric, 2, '.', ''), '0'), '.');

            return $observation->units
                ? "{$formatted} {$observation->units}"
                : $formatted;
        }

        if ($observation->value_text !== null && $observation->value_text !== '') {
            return $observation->value_text;
        }

        if ($observation->value_coded !== null && $observation->value_coded !== '') {
            return $observation->value_coded;
        }

        if ($observation->display !== null && $observation->display !== '') {
            return $observation->display;
        }

        return '—';
    }

    protected function formatReferenceRange(DiagnosticObservation $observation): ?string
    {
        if ($observation->reference_range_text) {
            return $observation->reference_range_text;
        }

        if ($observation->reference_range_min !== null || $observation->reference_range_max !== null) {
            $min = $observation->reference_range_min !== null
                ? rtrim(rtrim(number_format((float) $observation->reference_range_min, 2, '.', ''), '0'), '.')
                : null;
            $max = $observation->reference_range_max !== null
                ? rtrim(rtrim(number_format((float) $observation->reference_range_max, 2, '.', ''), '0'), '.')
                : null;
            $units = $observation->units ? " {$observation->units}" : '';

            if ($min !== null && $max !== null) {
                return "{$min} - {$max}{$units}";
            }

            if ($min !== null) {
                return ">= {$min}{$units}";
            }

            if ($max !== null) {
                return "<= {$max}{$units}";
            }
        }

        return null;
    }

    protected function resolveLatestCompletedTask(DiagnosticFulfillment $fulfillment): ?Task
    {
        return $fulfillment->requestItem
            ?->tasks()
            ->where('status', TaskStatus::COMPLETED)
            ->latest('completed_at')
            ->first();
    }
}
