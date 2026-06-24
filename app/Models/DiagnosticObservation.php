<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticObservationFactory;
use Modules\Diagnostics\Enums\AbnormalFlag;
use Modules\Diagnostics\Enums\ObservationStatus;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticObservation extends BaseModel
{
    /** @use HasFactory<DiagnosticObservationFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'profile_id',
        'parent_observation_id',
        'specimen_id',
        'code',
        'display',
        'status',
        'value_type',
        'value_numeric',
        'value_text',
        'value_coded',
        'value_boolean',
        'value_range_low',
        'value_range_high',
        'value_quantity_value',
        'value_quantity_unit',
        'data_absent_reason',
        'units',
        'reference_range_min',
        'reference_range_max',
        'reference_range_text',
        'abnormal_flag',
        'interpretation',
        'performed_by',
        'performed_at',
        'verified_by',
        'verified_at',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'status' => ObservationStatus::class,
        'value_numeric' => 'decimal:6',
        'value_boolean' => 'boolean',
        'value_range_low' => 'decimal:6',
        'value_range_high' => 'decimal:6',
        'value_quantity_value' => 'decimal:6',
        'reference_range_min' => 'decimal:6',
        'reference_range_max' => 'decimal:6',
        'abnormal_flag' => AbnormalFlag::class,
        'performed_at' => 'datetime',
        'verified_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    protected static function newFactory(): DiagnosticObservationFactory
    {
        return DiagnosticObservationFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DiagnosticServiceProfile::class, 'profile_id');
    }

    public function parentObservation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_observation_id');
    }

    public function childObservations(): HasMany
    {
        return $this->hasMany(self::class, 'parent_observation_id')
            ->orderBy('sort_order');
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSpecimen::class, 'specimen_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(DiagnosticObservationComponent::class, 'observation_id')
            ->orderBy('sort_order');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
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
