<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Clinical\Models\RequestItem;
use Modules\Core\Models\BaseModel;
use Modules\Core\Models\Branch;
use Modules\Diagnostics\Database\Factories\DiagnosticFulfillmentFactory;
use Modules\Diagnostics\Enums\AllocationStatus;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;
use Modules\Diagnostics\Enums\ReportVersionStatus;
use Modules\Diagnostics\Enums\SpecimenStatus;

/**
 * @property DiagnosticDiscipline $discipline
 * @property FulfillmentStatus $status
 * @property string|null $accession_number
 * @property string|null $request_item_id
 * @property string|null $branch_id
 * @property string|null $priority
 * @property string|null $clinical_indication
 * @property array|null $diagnosis_codes
 * @property \Illuminate\Support\Carbon|null $scheduled_at
 * @property \Illuminate\Support\Carbon|null $collection_date
 * @property-read DiagnosticReportVersion|null $latestReportVersion
 *
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticFulfillment extends BaseModel
{
    /** @use HasFactory<DiagnosticFulfillmentFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    protected $fillable = [
        'request_item_id',
        'branch_id',
        'discipline',
        'accession_number',
        'status',
        'priority',
        'clinical_indication',
        'diagnosis_codes',
        'scheduled_at',
        'collection_date',
        'cancelled_at',
        'cancelled_reason',
        'cancelled_by',
        'performer_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'discipline' => DiagnosticDiscipline::class,
        'status' => FulfillmentStatus::class,
        'diagnosis_codes' => 'array',
        'scheduled_at' => 'datetime',
        'collection_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function newFactory(): DiagnosticFulfillmentFactory
    {
        return DiagnosticFulfillmentFactory::new();
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(RequestItem::class, 'request_item_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performer_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function resultFiles(): HasMany
    {
        return $this->hasMany(DiagnosticResultFile::class, 'fulfillment_id');
    }

    public function specimens(): HasMany
    {
        return $this->hasMany(DiagnosticSpecimen::class, 'fulfillment_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(DiagnosticObservation::class, 'fulfillment_id');
    }

    public function reportVersions(): HasMany
    {
        return $this->hasMany(DiagnosticReportVersion::class, 'fulfillment_id')
            ->orderByDesc('version');
    }

    public function latestReportVersion(): HasOne
    {
        return $this->hasOne(DiagnosticReportVersion::class, 'fulfillment_id')
            ->ofMany('version', 'max');
    }

    public function study(): HasOne
    {
        return $this->hasOne(DiagnosticStudy::class, 'fulfillment_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(DiagnosticFulfillmentAllocation::class, 'fulfillment_id');
    }

    public function media(): HasManyThrough
    {
        return $this->hasManyThrough(
            DiagnosticMedia::class,
            DiagnosticStudy::class,
            'fulfillment_id',
            'study_id',
        );
    }

    public function hasCriticalReport(): bool
    {
        return (bool) $this->latestReportVersion?->is_critical;
    }

    public function schedule(
        ?\DateTimeInterface $scheduledAt = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
    ): void {
        $scheduledAt = Carbon::parse($scheduledAt ?? now())->startOfSecond();

        $this->update([
            'status' => FulfillmentStatus::SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);

        if ($this->discipline === DiagnosticDiscipline::RADIOLOGY) {
            $this->allocations()->firstOrCreate(
                [
                    'resource_type' => $resourceType ?? 'imaging_room',
                    'resource_id' => $resourceId ?? 'unassigned',
                ],
                [
                    'scheduled_start' => $scheduledAt,
                    'status' => AllocationStatus::SCHEDULED,
                ]
            );
        }
    }

    public function collectSpecimen(
        string $specimenType,
        ?User $collectedBy = null,
        ?\DateTimeInterface $collectedAt = null,
        array $attributes = [],
    ): DiagnosticSpecimen {
        $collectedAt = $collectedAt ?? now();
        $collectedBy = $collectedBy ?? auth()->user();

        $specimen = $this->specimens()->create(array_merge([
            'specimen_type' => $specimenType,
            'accession_number' => $this->accession_number,
            'status' => SpecimenStatus::COLLECTED,
            'collected_at' => $collectedAt,
            'collected_by' => $collectedBy?->id,
        ], $attributes));

        $this->update([
            'status' => FulfillmentStatus::COLLECTED,
            'collection_date' => $collectedAt,
        ]);

        return $specimen;
    }

    public function startProcessing(): void
    {
        $this->update([
            'status' => FulfillmentStatus::IN_PROGRESS,
        ]);
    }

    public function finalizeResult(string $reportStatus = 'final', array $attributes = []): DiagnosticReportVersion
    {
        $latestVersion = $this->latestReportVersion;
        $versionNumber = $latestVersion?->version ? $latestVersion->version + 1 : 1;

        $reportVersion = $this->reportVersions()->create(array_merge([
            'version' => $versionNumber,
            'status' => $reportStatus,
        ], $attributes));

        $this->update([
            'status' => FulfillmentStatus::COMPLETED,
        ]);

        return $reportVersion;
    }

    public function verifyResult(?User $verifiedBy = null): ?DiagnosticReportVersion
    {
        $reportVersion = $this->fresh()->latestReportVersion;

        if ($reportVersion === null) {
            return null;
        }

        $verifiedBy = $verifiedBy ?? auth()->user();

        $reportVersion->update([
            'status' => ReportVersionStatus::FINAL,
            'verified_by' => $verifiedBy?->id,
            'verified_at' => now(),
        ]);

        $this->update([
            'status' => FulfillmentStatus::COMPLETED,
        ]);

        return $reportVersion->fresh();
    }

    public function signReport(User $user, ?string $role = null, ?string $notes = null): ?DiagnosticReportSignature
    {
        $reportVersion = $this->fresh()->latestReportVersion;

        if ($reportVersion === null) {
            return null;
        }

        return $reportVersion->signatures()->create([
            'signed_by' => $user->id,
            'role' => $role,
            'notes' => $notes,
        ]);
    }

    public function amendReport(): DiagnosticReportVersion
    {
        return $this->finalizeResult(ReportVersionStatus::AMENDED->value);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => FulfillmentStatus::CANCELLED,
        ]);
    }

    public function getTitleAttribute(): string
    {
        if (filled($this->accession_number)) {
            return (string) $this->accession_number;
        }

        $serviceName = $this->relationLoaded('requestItem')
            ? $this->requestItem?->service?->name
            : $this->requestItem()->with('service')->first()?->service?->name;

        return $serviceName ?? $this->discipline?->getLabel() ?? 'Fulfillment';
    }
}
