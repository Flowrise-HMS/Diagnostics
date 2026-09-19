<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Diagnostics\Database\Factories\DiagnosticPanelFactory;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticPanel extends Model
{
    /** @use HasFactory<DiagnosticPanelFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'profile_id',
    ];

    protected static function newFactory(): DiagnosticPanelFactory
    {
        return DiagnosticPanelFactory::new();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(DiagnosticServiceProfile::class, 'profile_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DiagnosticPanelItem::class, 'panel_id')
            ->orderBy('sequence');
    }
}
