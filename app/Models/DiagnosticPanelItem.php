<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;
use Modules\Diagnostics\Database\Factories\DiagnosticPanelItemFactory;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticPanelItem extends BaseModel
{
    /** @use HasFactory<DiagnosticPanelItemFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'panel_id',
        'child_profile_id',
        'sequence',
        'is_required',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'is_required' => 'boolean',
    ];

    protected static function newFactory(): DiagnosticPanelItemFactory
    {
        return DiagnosticPanelItemFactory::new();
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(DiagnosticPanel::class, 'panel_id');
    }

    public function childProfile(): BelongsTo
    {
        return $this->belongsTo(DiagnosticServiceProfile::class, 'child_profile_id');
    }
}
