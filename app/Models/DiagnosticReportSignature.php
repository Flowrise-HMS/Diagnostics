<?php

namespace Modules\Diagnostics\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class DiagnosticReportSignature extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'report_version_id',
        'signed_by',
        'role',
        'signed_at',
        'notes',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function reportVersion(): BelongsTo
    {
        return $this->belongsTo(DiagnosticReportVersion::class, 'report_version_id');
    }

    public function signedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
