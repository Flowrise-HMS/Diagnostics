<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticReportSignatureFactory;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticReportSignature extends BaseModel
{
    /** @use HasFactory<DiagnosticReportSignatureFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'report_version_id',
        'signed_by',
        'signature_type',
        'signature',
        'role',
        'signed_at',
        'notes',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    protected static function newFactory(): DiagnosticReportSignatureFactory
    {
        return DiagnosticReportSignatureFactory::new();
    }

    public function reportVersion(): BelongsTo
    {
        return $this->belongsTo(DiagnosticReportVersion::class, 'report_version_id');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
