<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticObservationFactory;

class DiagnosticObservation extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'specimen_id',
        'code',
        'status',
    ];

    protected static function newFactory(): DiagnosticObservationFactory
    {
        return DiagnosticObservationFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSpecimen::class, 'specimen_id');
    }

    public function reportVersions(): BelongsToMany
    {
        return $this->belongsToMany(
            DiagnosticReportVersion::class,
            'diagnostic_report_observations',
            'observation_id',
            'report_version_id'
        )->withPivot('sort_order');
    }
}
