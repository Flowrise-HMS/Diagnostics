<?php

namespace Modules\Diagnostics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Core\Models\BaseModel;
use Modules\Core\Models\Service;
use Modules\Diagnostics\Database\Factories\DiagnosticServiceProfileFactory;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;

/**
 * @property DiagnosticDiscipline|null $discipline
 *
 * @method static static create(array<string, mixed> $attributes = [])
 */
class DiagnosticServiceProfile extends BaseModel
{
    /** @use HasFactory<DiagnosticServiceProfileFactory> */
    use HasFactory;

    use HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'service_id',
        'discipline',
        'loinc_code',
        'loinc_display',
        'default_specimen_type',
        'preparation_instructions',
        'auto_verify_eligible',
        'turnaround_time_minutes',
        'modality',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'discipline' => DiagnosticDiscipline::class,
        'auto_verify_eligible' => 'boolean',
        'turnaround_time_minutes' => 'integer',
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

    public function panel(): HasOne
    {
        return $this->hasOne(DiagnosticPanel::class, 'profile_id');
    }

    public function panelItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            DiagnosticPanelItem::class,
            DiagnosticPanel::class,
            'profile_id',
            'panel_id',
            'id',
            'id',
        )->orderBy('diagnostic_panel_items.sequence');
    }

    public function ensurePanel(): DiagnosticPanel
    {
        return $this->panel()->firstOrCreate([]);
    }

    public function referenceRanges(): HasMany
    {
        return $this->hasMany(DiagnosticReferenceRange::class, 'profile_id');
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

    /**
     * The result fields an operator fills in for this service, i.e. the fields of its
     * default template. Admins edit them inline on the profile.
     */
    public function resultFields(): HasManyThrough
    {
        return $this->hasManyThrough(
            DiagnosticResultTemplateField::class,
            DiagnosticResultTemplate::class,
            'profile_id',
            'template_id',
            'id',
            'id',
        )
            ->where('diagnostic_result_templates.is_default', true)
            ->where('diagnostic_result_templates.is_active', true)
            ->orderBy('diagnostic_result_template_fields.sort_order');
    }

    /**
     * @return array<string, string> Field id => label, for numeric fields only.
     */
    public function numericResultFieldOptions(): array
    {
        return $this->resultFields()
            ->where('diagnostic_result_template_fields.value_type', 'numeric')
            ->get()
            ->mapWithKeys(fn (DiagnosticResultTemplateField $field): array => [$field->id => $field->label])
            ->all();
    }

    public function getTitleAttribute(): string
    {
        $serviceName = $this->relationLoaded('service')
            ? $this->service?->name
            : $this->service()->value('name');

        $discipline = $this->discipline?->getLabel();

        if (filled($serviceName) && filled($discipline)) {
            return "{$serviceName} ({$discipline})";
        }

        return $serviceName ?? $discipline ?? 'Diagnostic Profile';
    }
}
