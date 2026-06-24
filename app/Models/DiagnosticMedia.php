<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticMediaFactory;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticMedia extends BaseModel
{
    /** @use HasFactory<DiagnosticMediaFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'study_id',
        'uid',
        'series_uid',
        'series_number',
        'instance_number',
        'sop_class',
        'modality',
        'file_type',
        'file_name',
        'file_path',
        'mime_type',
        'thumbnail_path',
        'viewer_url',
        'is_key_image',
    ];

    protected $casts = [
        'series_number' => 'integer',
        'instance_number' => 'integer',
        'is_key_image' => 'boolean',
    ];

    protected static function newFactory(): DiagnosticMediaFactory
    {
        return DiagnosticMediaFactory::new();
    }

    public function study(): BelongsTo
    {
        return $this->belongsTo(DiagnosticStudy::class, 'study_id');
    }
}
