<?php

namespace Modules\Diagnostics\Classes\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Core\Models\Service;
use Modules\Core\Models\ServiceCategory;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticResultTemplateField;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

/**
 * Keeps the diagnostic catalog in step with the Core service catalog.
 *
 * Diagnostics only ever reads `services`; it writes its own profile rows so that
 * pricing, pharmacy and billing behaviour of a service is never touched from here.
 */
class DiagnosticCatalogService
{
    /** @var list<string> */
    public const DIAGNOSTIC_CATEGORY_CODES = ['LAB', 'RAD', 'PAT', 'DIA'];

    /**
     * Raw category code. The enum cast returns null for codes outside the enum
     * (the seeded pathology category is `PAT`), so read the stored value.
     */
    public static function categoryCode(?ServiceCategory $category): ?string
    {
        if ($category === null) {
            return null;
        }

        $code = $category->getRawOriginal('code') ?? $category->getAttributes()['code'] ?? null;

        return is_string($code) && $code !== '' ? strtoupper($code) : null;
    }

    public static function isDiagnosticCategory(?ServiceCategory $category): bool
    {
        return in_array(self::categoryCode($category), self::DIAGNOSTIC_CATEGORY_CODES, true);
    }

    /**
     * Create the diagnostic setup for a service in a diagnostic category if it has none.
     * Returns null for services outside those categories; existing profiles are untouched.
     */
    public function ensureProfileForService(Service $service): ?DiagnosticServiceProfile
    {
        $service->loadMissing('category');

        $discipline = DiagnosticDiscipline::fromServiceCategoryCode(self::categoryCode($service->category));

        if ($discipline === null) {
            return null;
        }

        return DiagnosticServiceProfile::query()
            ->withoutGlobalScope('branch')
            ->firstOrCreate(
                ['service_id' => $service->id],
                [
                    'branch_id' => $service->branch_id,
                    'discipline' => $discipline,
                    'is_active' => true,
                    'metadata' => ['seed_source' => 'auto_setup'],
                ],
            );
    }

    /**
     * Backfill profiles for every active service already sitting in a diagnostic category.
     *
     * @return array{created: int, existing: int}
     */
    public function syncProfilesFromServiceCatalog(): array
    {
        $result = ['created' => 0, 'existing' => 0];

        Service::query()
            ->withoutGlobalScope('branch')
            ->with('category')
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->whereIn('code', self::DIAGNOSTIC_CATEGORY_CODES))
            ->orderBy('id')
            ->chunkById(200, function (Collection $services) use (&$result): void {
                DB::transaction(function () use ($services, &$result): void {
                    foreach ($services as $service) {
                        $profile = $this->ensureProfileForService($service);

                        if ($profile === null) {
                            continue;
                        }

                        $profile->wasRecentlyCreated ? $result['created']++ : $result['existing']++;
                    }
                });
            });

        return $result;
    }

    /**
     * Every profile owns exactly one default template; admins never see it, they
     * simply edit "result fields" on the diagnostic service.
     */
    public function ensureDefaultTemplate(DiagnosticServiceProfile $profile): DiagnosticResultTemplate
    {
        $template = DiagnosticResultTemplate::query()
            ->where('profile_id', $profile->id)
            ->where('is_default', true)
            ->orderByDesc('is_active')
            ->first();

        if ($template === null) {
            $template = DiagnosticResultTemplate::query()->create([
                'profile_id' => $profile->id,
                'name' => "{$profile->title} results",
                'is_default' => true,
                'is_active' => true,
            ]);
        } elseif (! $template->is_active) {
            $template->update(['is_active' => true]);
        }

        DiagnosticResultTemplate::query()
            ->where('profile_id', $profile->id)
            ->whereKeyNot($template->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        return $template;
    }

    /**
     * Rows as the profile form expects them, in display order.
     *
     * @return list<array<string, mixed>>
     */
    public function resultFieldRows(DiagnosticServiceProfile $profile): array
    {
        return $profile->resultFields()
            ->get()
            ->map(fn (DiagnosticResultTemplateField $field): array => [
                'id' => $field->id,
                'label' => $field->label,
                'field_key' => $field->field_key,
                'value_type' => $field->value_type,
                'default_units' => $field->default_units,
                'reference_range_low' => $field->reference_range_low,
                'reference_range_high' => $field->reference_range_high,
                'is_required' => (bool) $field->is_required,
                'options' => is_array($field->options) ? array_values($field->options) : [],
                'observation_code' => $field->observation_code,
            ])
            ->values()
            ->all();
    }

    /**
     * Replace the profile's result fields with the submitted rows. Rows carrying an `id`
     * are updated in place (so their reference ranges survive); the rest are created and
     * anything no longer submitted is deleted, cascading to its ranges.
     *
     * @param  list<array<string, mixed>>  $rows
     *
     * @throws ValidationException
     */
    public function syncResultFields(DiagnosticServiceProfile $profile, array $rows): DiagnosticResultTemplate
    {
        $rows = $this->normaliseResultFieldRows($rows);

        return DB::transaction(function () use ($profile, $rows): DiagnosticResultTemplate {
            $template = $this->ensureDefaultTemplate($profile);

            $existing = $template->fields()->get()->keyBy('id');
            $keptIds = [];

            foreach ($rows as $row) {
                $id = $row['id'] ?? null;
                unset($row['id']);

                $field = $id !== null ? $existing->get($id) : null;

                if ($field === null) {
                    $field = $template->fields()->create($row);
                } else {
                    $field->fill($row)->save();
                }

                $keptIds[] = $field->id;
            }

            $template->fields()->whereKeyNot($keptIds)->delete();

            return $template->fresh(['fields']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     *
     * @throws ValidationException
     */
    protected function normaliseResultFieldRows(array $rows): array
    {
        $normalised = [];
        $seenKeys = [];

        foreach (array_values($rows) as $position => $row) {
            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '') {
                throw ValidationException::withMessages(['result_fields' => 'Every result field needs a label.']);
            }

            $key = Str::slug(trim((string) ($row['field_key'] ?? '')), '_');

            if ($key === '') {
                $key = Str::slug($label, '_');
            }

            if (in_array($key, $seenKeys, true)) {
                throw ValidationException::withMessages(['result_fields' => "The field key \"{$key}\" is used more than once."]);
            }

            $seenKeys[] = $key;

            $valueType = $row['value_type'] ?? 'text';
            $valueType = $valueType instanceof \BackedEnum ? (string) $valueType->value : (string) $valueType;
            $options = $row['options'] ?? [];

            if (is_string($options)) {
                $options = explode(',', $options);
            }

            $options = array_values(array_filter(array_map('trim', array_map('strval', (array) $options)), 'strlen'));

            $normalised[] = [
                'id' => filled($row['id'] ?? null) ? (string) $row['id'] : null,
                'label' => $label,
                'field_key' => $key,
                'value_type' => $valueType,
                'data_type' => $valueType,
                'default_units' => $valueType === 'numeric' ? (filled($row['default_units'] ?? null) ? trim((string) $row['default_units']) : null) : null,
                'reference_range_low' => $valueType === 'numeric' && filled($row['reference_range_low'] ?? null) ? $row['reference_range_low'] : null,
                'reference_range_high' => $valueType === 'numeric' && filled($row['reference_range_high'] ?? null) ? $row['reference_range_high'] : null,
                'is_required' => (bool) ($row['is_required'] ?? false),
                'options' => $valueType === 'select' ? $options : null,
                'observation_code' => filled($row['observation_code'] ?? null) ? trim((string) $row['observation_code']) : null,
                'observation_name' => $label,
                'sort_order' => $position,
            ];
        }

        return $normalised;
    }
}
