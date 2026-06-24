<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticReferenceRangeFactory;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticReferenceRange extends BaseModel
{
    /** @use HasFactory<DiagnosticReferenceRangeFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'profile_id',
        'gender',
        'age_min_months',
        'age_max_months',
        'min_value',
        'max_value',
        'range_text',
        'units',
        'critical_low',
        'critical_high',
    ];

    protected $casts = [
        'age_min_months' => 'integer',
        'age_max_months' => 'integer',
        'min_value' => 'decimal:6',
        'max_value' => 'decimal:6',
        'critical_low' => 'decimal:6',
        'critical_high' => 'decimal:6',
    ];

    protected static function newFactory(): DiagnosticReferenceRangeFactory
    {
        return DiagnosticReferenceRangeFactory::new();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DiagnosticServiceProfile::class, 'profile_id');
    }
}
