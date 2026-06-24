<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticObservationComponentFactory;
use Modules\Diagnostics\Enums\AbnormalFlag;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticObservationComponent extends BaseModel
{
    /** @use HasFactory<DiagnosticObservationComponentFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'observation_id',
        'code',
        'display',
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
        'sort_order',
    ];

    protected $casts = [
        'value_numeric' => 'decimal:6',
        'value_boolean' => 'boolean',
        'value_range_low' => 'decimal:6',
        'value_range_high' => 'decimal:6',
        'value_quantity_value' => 'decimal:6',
        'reference_range_min' => 'decimal:6',
        'reference_range_max' => 'decimal:6',
        'abnormal_flag' => AbnormalFlag::class,
        'sort_order' => 'integer',
    ];

    protected static function newFactory(): DiagnosticObservationComponentFactory
    {
        return DiagnosticObservationComponentFactory::new();
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(DiagnosticObservation::class, 'observation_id');
    }
}
