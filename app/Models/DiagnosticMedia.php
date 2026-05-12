<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticMediaFactory;

class DiagnosticMedia extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'study_id',
        'file_type',
        'file_name',
        'file_path',
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
