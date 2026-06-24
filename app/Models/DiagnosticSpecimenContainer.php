<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticSpecimenContainerFactory;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticSpecimenContainer extends BaseModel
{
    /** @use HasFactory<DiagnosticSpecimenContainerFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'specimen_id',
        'container_type',
        'additive',
        'capacity',
        'capacity_unit',
        'identifier',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
    ];

    protected static function newFactory(): DiagnosticSpecimenContainerFactory
    {
        return DiagnosticSpecimenContainerFactory::new();
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSpecimen::class, 'specimen_id');
    }
}
