<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticReportVersionFactory;

class DiagnosticReportVersion extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'version',
        'status',
    ];

    protected static function newFactory(): DiagnosticReportVersionFactory
    {
        return DiagnosticReportVersionFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
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
