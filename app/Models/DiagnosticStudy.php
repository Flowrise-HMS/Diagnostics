<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticStudyFactory;
use Modules\Diagnostics\Enums\StudyStatus;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticStudy extends BaseModel
{
    /** @use HasFactory<DiagnosticStudyFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'uid',
        'accession_number',
        'modality',
        'body_site',
        'performed_at',
        'performed_by',
        'interpreter_id',
        'number_of_series',
        'conclusion',
        'status',
        'metadata',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'number_of_series' => 'integer',
        'status' => StudyStatus::class,
        'metadata' => 'array',
    ];

    protected static function newFactory(): DiagnosticStudyFactory
    {
        return DiagnosticStudyFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function interpreter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interpreter_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(DiagnosticMedia::class, 'study_id');
    }
}
