<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Diagnostics\Database\Factories\DiagnosticResultTemplateFieldFactory;

class DiagnosticResultTemplateField extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'template_id',
        'field_key',
        'label',
        'value_type',
        'options',
        'sort_order',
    ];

    protected $casts = [
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
