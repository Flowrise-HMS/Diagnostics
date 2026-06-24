<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticResultFileFactory;
use Modules\Diagnostics\Enums\FileSourceType;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticResultFile extends BaseModel
{
    /** @use HasFactory<DiagnosticResultFileFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'fulfillment_id',
        'report_version_id',
        'file_type',
        'source',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'checksum',
        'is_authoritative',
        'uploaded_by',
        'notes',
    ];

    protected $casts = [
        'source' => FileSourceType::class,
        'file_size' => 'integer',
        'is_authoritative' => 'boolean',
    ];

    protected static function newFactory(): DiagnosticResultFileFactory
    {
        return DiagnosticResultFileFactory::new();
    }

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(DiagnosticFulfillment::class, 'fulfillment_id');
    }

    public function reportVersion(): BelongsTo
    {
        return $this->belongsTo(DiagnosticReportVersion::class, 'report_version_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
