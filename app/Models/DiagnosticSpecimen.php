<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticSpecimenFactory;
use Modules\Diagnostics\Enums\SpecimenCondition;
use Modules\Diagnostics\Enums\SpecimenStatus;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticSpecimen extends BaseModel
{
    /** @use HasFactory<DiagnosticSpecimenFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'parent_specimen_id',
        'accession_number',
        'specimen_type',
        'specimen_class',
        'collection_method',
        'body_site',
        'fasting_hours',
        'volume',
        'volume_unit',
        'container_type',
        'container_id',
        'barcode',
        'collected_at',
        'collected_by',
        'received_at',
        'condition',
        'condition_note',
        'status',
        'storage_location',
    ];

    protected $casts = [
        'fasting_hours' => 'integer',
        'volume' => 'decimal:2',
        'collected_at' => 'datetime',
        'received_at' => 'datetime',
        'condition' => SpecimenCondition::class,
        'status' => SpecimenStatus::class,
    ];

    protected static function newFactory(): DiagnosticSpecimenFactory
    {
        return DiagnosticSpecimenFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }

    public function parentSpecimen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_specimen_id');
    }

    public function childSpecimens(): HasMany
    {
        return $this->hasMany(self::class, 'parent_specimen_id');
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(DiagnosticObservation::class, 'specimen_id');
    }

    public function containers(): HasMany
    {
        return $this->hasMany(DiagnosticSpecimenContainer::class, 'specimen_id');
    }

    public function processingEvents(): HasMany
    {
        return $this->hasMany(DiagnosticSpecimenProcessingEvent::class, 'specimen_id');
    }
}
