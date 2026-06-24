<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticFulfillmentAllocationFactory;
use Modules\Diagnostics\Enums\AllocationStatus;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticFulfillmentAllocation extends BaseModel
{
    /** @use HasFactory<DiagnosticFulfillmentAllocationFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'resource_type',
        'resource_id',
        'scheduled_start',
        'scheduled_end',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'status' => AllocationStatus::class,
    ];

    protected static function newFactory(): DiagnosticFulfillmentAllocationFactory
    {
        return DiagnosticFulfillmentAllocationFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }
}
