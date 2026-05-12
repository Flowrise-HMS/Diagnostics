<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\BaseModel;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Database\Factories\DiagnosticServiceProfileFactory;

class DiagnosticServiceProfile extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'service_id',
        'discipline',
        'loinc_code',
        'loinc_display',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function newFactory(): DiagnosticServiceProfileFactory
    {
        return DiagnosticServiceProfileFactory::new();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(DiagnosticResultTemplate::class, 'profile_id');
    }

    public function defaultTemplate(): HasOne
    {
        return $this->hasOne(DiagnosticResultTemplate::class, 'profile_id')
            ->where('is_default', true)
            ->where('is_active', true);
    }
}
