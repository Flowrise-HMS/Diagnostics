<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Diagnostics\Database\Factories\DiagnosticResultTemplateFieldFactory;

/**
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticResultTemplateField extends Model
{
    /** @use HasFactory<DiagnosticResultTemplateFieldFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'template_id',
        'observation_code',
        'observation_name',
        'data_type',
        'field_key',
        'label',
        'value_type',
        'default_units',
        'is_required',
        'reference_range_low',
        'reference_range_high',
        'options',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'reference_range_low' => 'decimal:6',
        'reference_range_high' => 'decimal:6',
        'sort_order' => 'integer',
        'options' => 'array',
    ];

    protected static function newFactory(): DiagnosticResultTemplateFieldFactory
    {
        return DiagnosticResultTemplateFieldFactory::new();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DiagnosticResultTemplate::class, 'template_id');
    }
}
