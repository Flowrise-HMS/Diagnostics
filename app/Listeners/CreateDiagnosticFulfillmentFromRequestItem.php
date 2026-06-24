<?php

namespace Modules\Diagnostics\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\Clinical\Events\RequestItemCreated;
use Modules\Clinical\Models\ServiceRequest;
use Modules\Core\Settings\DiagnosticsSettings;
use Modules\Core\Support\AppSettings;
use Modules\Diagnostics\Classes\DiagnosticNumberGenerator;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Enums\StudyStatus;
use Modules\Diagnostics\Models\DiagnosticFulfillment;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class CreateDiagnosticFulfillmentFromRequestItem
{
    public function __construct(
        protected DiagnosticNumberGenerator $numberGenerator,
    ) {}

    public function handle(RequestItemCreated $event): void
    {
        try {
            if (! app(AppSettings::class)->features()->diagnostics_enabled) {
                return;
            }
            if (! app(DiagnosticsSettings::class)->auto_create_fulfillment) {
                return;
            }
        } catch (\Throwable) {
        }

        $requestItem = $event->requestItem->loadMissing(['serviceRequest.branch', 'service']);

        $profile = DiagnosticServiceProfile::query()
            ->where('service_id', $requestItem->service_id)
            ->where('is_active', true)
            ->first();

        if ($profile === null) {
            return;
        }

        $serviceRequest = $requestItem->serviceRequest;

        DB::transaction(function () use ($requestItem, $profile, $serviceRequest): void {
            $fulfillment = DiagnosticFulfillment::query()->firstOrCreate(
                ['request_item_id' => $requestItem->id],
                [
                    'branch_id' => $serviceRequest->branch_id,
                    'discipline' => $profile->discipline,
                    'status' => FulfillmentStatus::PENDING,
                    'accession_number' => $this->numberGenerator->generateAccessionNumber((string) $serviceRequest->branch_id),
                    'priority' => $serviceRequest->priority?->value ?? 'routine',
                    'clinical_indication' => $this->resolveClinicalIndication($serviceRequest),
                ]
            );

            if ($profile->discipline === DiagnosticDiscipline::RADIOLOGY) {
                $fulfillment->study()->firstOrCreate(
                    ['fulfillment_id' => $fulfillment->id],
                    [
                        'modality' => $profile->modality,
                        'body_site' => $profile->metadata['body_site'] ?? null,
                        'accession_number' => $fulfillment->accession_number,
                        'status' => StudyStatus::REGISTERED,
                    ]
                );
            }

            if (
                in_array($profile->discipline, [DiagnosticDiscipline::LAB, DiagnosticDiscipline::PATHOLOGY], true)
                && filled($profile->default_specimen_type)
            ) {
                $fulfillment->specimens()->firstOrCreate(
                    [
                        'fulfillment_id' => $fulfillment->id,
                        'specimen_type' => $profile->default_specimen_type,
                    ],
                    [
                        'accession_number' => $fulfillment->accession_number,
                    ]
                );
            }
        });
    }

    protected function resolveClinicalIndication(ServiceRequest $serviceRequest): ?string
    {
        $metadata = $serviceRequest->metadata;

        if (is_array($metadata) && filled($metadata['clinical_indication'] ?? null)) {
            return (string) $metadata['clinical_indication'];
        }

        return filled($serviceRequest->notes) ? $serviceRequest->notes : null;
    }
}
