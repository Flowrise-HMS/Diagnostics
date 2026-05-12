<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticSpecimenFactory;

class DiagnosticSpecimen extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'specimen_type',
        'status',
    ];

    protected static function newFactory(): DiagnosticSpecimenFactory
    {
        return DiagnosticSpecimenFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(DiagnosticObservation::class, 'specimen_id');
    }
}
