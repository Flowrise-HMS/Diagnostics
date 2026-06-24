<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticReportVersionFactory;
use Modules\Diagnostics\Enums\ReportVersionStatus;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticReportVersion extends BaseModel
{
    /** @use HasFactory<DiagnosticReportVersionFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'report_number',
        'version',
        'title',
        'status',
        'conclusion',
        'conclusion_codes',
        'performed_by',
        'verified_by',
        'verified_at',
        'is_critical',
        'critical_notified_at',
        'metadata',
    ];

    protected $casts = [
        'version' => 'integer',
        'status' => ReportVersionStatus::class,
        'conclusion_codes' => 'array',
        'verified_at' => 'datetime',
        'is_critical' => 'boolean',
        'critical_notified_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function newFactory(): DiagnosticReportVersionFactory
    {
        return DiagnosticReportVersionFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function observations(): BelongsToMany
    {
        return $this->belongsToMany(
            DiagnosticObservation::class,
            'diagnostic_report_observations',
            'report_version_id',
            'observation_id'
        )->withPivot('sort_order');
    }

    public function resultFiles(): HasMany
    {
        return $this->hasMany(DiagnosticResultFile::class, 'report_version_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(DiagnosticReportSignature::class, 'report_version_id')
            ->latest('signed_at');
    }
}
