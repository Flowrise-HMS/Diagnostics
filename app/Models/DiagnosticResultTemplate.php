<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticResultTemplateFactory;

class DiagnosticResultTemplate extends BaseModel
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'profile_id',
        'name',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): DiagnosticResultTemplateFactory
    {
        return DiagnosticResultTemplateFactory::new();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DiagnosticServiceProfile::class, 'profile_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DiagnosticResultTemplateField::class, 'template_id')
            ->orderBy('sort_order');
    }
}
