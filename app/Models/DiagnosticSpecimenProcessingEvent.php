<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticSpecimenProcessingEventFactory;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticSpecimenProcessingEvent extends BaseModel
{
    /** @use HasFactory<DiagnosticSpecimenProcessingEventFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'specimen_id',
        'procedure',
        'additive',
        'processed_by',
        'processed_at',
        'description',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    protected static function newFactory(): DiagnosticSpecimenProcessingEventFactory
    {
        return DiagnosticSpecimenProcessingEventFactory::new();
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(DiagnosticSpecimen::class, 'specimen_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
