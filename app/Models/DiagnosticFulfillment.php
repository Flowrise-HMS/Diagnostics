<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Clinical\Models\RequestItem;
use Modules\Core\Models\BaseModel;
use Modules\Core\Models\Branch;
use Modules\Diagnostics\Database\Factories\DiagnosticFulfillmentFactory;
use Modules\Diagnostics\Enums\FulfillmentStatus;

class DiagnosticFulfillment extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'request_item_id',
        'branch_id',
        'discipline',
        'status',
    ];

    protected $casts = [
        'status' => FulfillmentStatus::class,
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

    public function schedule(): void
    {
        $this->update([
            'status' => FulfillmentStatus::SCHEDULED,
        ]);
    }

    public function collectSpecimen(string $specimenType): DiagnosticSpecimen
    {
        $specimen = $this->specimens()->create([
            'specimen_type' => $specimenType,
            'status' => 'collected',
        ]);

        $this->update([
            'status' => FulfillmentStatus::COLLECTED,
        ]);

        return $specimen;
    }

    public function startProcessing(): void
    {
        $this->update([
            'status' => FulfillmentStatus::IN_PROGRESS,
        ]);
    }

    public function finalizeResult(string $reportStatus = 'final'): DiagnosticReportVersion
    {
        $latestVersion = $this->latestReportVersion;
        $versionNumber = $latestVersion?->version ? $latestVersion->version + 1 : 1;

        $reportVersion = $this->reportVersions()->create([
            'version' => $versionNumber,
            'status' => $reportStatus,
        ]);

        $this->update([
            'status' => FulfillmentStatus::COMPLETED,
        ]);

        return $reportVersion;
    }

    public function verifyResult(): ?DiagnosticReportVersion
    {
        $reportVersion = $this->latestReportVersion;

        if ($reportVersion === null) {
            return null;
        }

        $reportVersion->update([
            'status' => 'final',
        ]);

        $this->update([
            'status' => FulfillmentStatus::COMPLETED,
        ]);

        return $reportVersion->fresh();
    }

    public function signReport(User $user, ?string $role = null, ?string $notes = null): ?DiagnosticReportSignature
    {
        $reportVersion = $this->latestReportVersion;

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
        return $this->finalizeResult('amended');
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => FulfillmentStatus::CANCELLED,
        ]);
    }
}
