<?php

namespace Modules\Diagnostics\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Models\Service;
use Modules\Core\Models\ServiceCategory;
use Modules\Diagnostics\Models\DiagnosticPanelItem;
use Modules\Diagnostics\Models\DiagnosticReferenceRange;
use Modules\Diagnostics\Models\DiagnosticResultTemplate;
use Modules\Diagnostics\Models\DiagnosticServiceProfile;

class DiagnosticStarterCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'LAB' => $this->ensureCategory(
                code: 'LAB',
                name: 'Laboratory',
                description: 'Blood tests, urinalysis, and diagnostic lab services',
                sortOrder: 3,
            ),
            'RAD' => $this->ensureCategory(
                code: 'RAD',
                name: 'Radiology',
                description: 'X-rays, CT scans, ultrasounds, and other imaging services',
                sortOrder: 2,
            ),
            'PAT' => $this->ensureCategory(
                code: 'PAT',
                name: 'Pathology',
                description: 'Tissue, cytology, and histopathology diagnostic services',
                sortOrder: 9,
            ),
        ];

        foreach ($this->starterCatalog() as $entry) {
            $category = $categories[$entry['category_code']];
            $service = $this->findOrCreateService($category, $entry);
            $profile = $this->ensureProfile($service, $entry);

            $template = DiagnosticResultTemplate::query()->updateOrCreate(
                [
                    'profile_id' => $profile->id,
                    'name' => $entry['template']['name'],
                ],
                [
                    'is_default' => true,
                    'is_active' => true,
                ]
            );

            DiagnosticResultTemplate::query()
                ->where('profile_id', $profile->id)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);

            foreach ($entry['template']['fields'] as $index => $field) {
                $template->fields()->updateOrCreate(
                    ['field_key' => $field['field_key']],
                    [
                        'label' => $field['label'],
                        'value_type' => $field['value_type'],
                        'observation_code' => $field['observation_code'] ?? null,
                        'observation_name' => $field['observation_name'] ?? $field['label'],
                        'data_type' => $field['data_type'] ?? $field['value_type'],
                        'default_units' => $field['default_units'] ?? null,
                        'is_required' => $field['is_required'] ?? false,
                        'reference_range_low' => $field['reference_range_low'] ?? null,
                        'reference_range_high' => $field['reference_range_high'] ?? null,
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }

        $this->seedPanels();
        $this->seedStarterReferenceRanges();
    }

    protected function ensureCategory(string $code, string $name, string $description, int $sortOrder): ServiceCategory
    {
        return ServiceCategory::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'description' => $description,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]
        );
    }

    /**
     * @param  array{name: string, description: string, discipline: string, category_code: string, loinc_code: ?string, loinc_display: ?string, service_defaults?: array<string, mixed>, template: array{name: string, fields: list<array{field_key: string, label: string, value_type: string}>}}  $entry
     */
    protected function findOrCreateService(ServiceCategory $category, array $entry): Service
    {
        $existing = Service::query()
            ->where('category_id', $category->id)
            ->where('name', $entry['name'])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $defaults = array_merge([
            'description' => $entry['description'],
            'price' => 50.00,
            'requires_payment_before' => true,
            'requires_prescription' => true,
            'is_billable' => true,
            'is_active' => true,
            'estimated_duration_minutes' => 15,
            'metadata' => [
                'seed_source' => 'diagnostics_starter_catalog',
                'starter_pack' => 'small_clinic',
            ],
        ], $entry['service_defaults'] ?? []);

        return Service::query()->create(array_merge($defaults, [
            'name' => $entry['name'],
            'category_id' => $category->id,
        ]));
    }

    /**
     * @param  array{name: string, description: string, discipline: string, category_code: string, loinc_code: ?string, loinc_display: ?string, service_defaults?: array<string, mixed>, template: array{name: string, fields: list<array{field_key: string, label: string, value_type: string}>}}  $entry
     */
    protected function ensureProfile(Service $service, array $entry): DiagnosticServiceProfile
    {
        return DiagnosticServiceProfile::query()->updateOrCreate(
            ['service_id' => $service->id],
            [
                'discipline' => $entry['discipline'],
                'loinc_code' => $entry['loinc_code'],
                'loinc_display' => $entry['loinc_display'],
                'modality' => $entry['modality'] ?? null,
                'default_specimen_type' => $entry['default_specimen_type'] ?? null,
                'is_active' => true,
                'metadata' => [
                    'seed_source' => 'diagnostics_starter_catalog',
                    'starter_pack' => 'small_clinic',
                ],
            ]
        );
    }

    protected function seedPanels(): void
    {
        $analyteProfiles = $this->ensureAnalyteComponentProfiles();

        $panelDefinitions = [
            'Full Blood Count (FBC)' => [
                ['analyte' => 'hemoglobin', 'sequence' => 1, 'is_required' => true],
                ['analyte' => 'pcv', 'sequence' => 2, 'is_required' => true],
                ['analyte' => 'wbc', 'sequence' => 3, 'is_required' => true],
                ['analyte' => 'platelets', 'sequence' => 4, 'is_required' => true],
            ],
            'Lipid Profile' => [
                ['analyte' => 'total_cholesterol', 'sequence' => 1, 'is_required' => true],
                ['analyte' => 'hdl', 'sequence' => 2, 'is_required' => true],
                ['analyte' => 'ldl', 'sequence' => 3, 'is_required' => true],
                ['analyte' => 'triglycerides', 'sequence' => 4, 'is_required' => true],
            ],
            'Liver Function Test' => [
                ['analyte' => 'alt', 'sequence' => 1, 'is_required' => true],
                ['analyte' => 'ast', 'sequence' => 2, 'is_required' => true],
                ['analyte' => 'alp', 'sequence' => 3, 'is_required' => true],
                ['analyte' => 'bilirubin_total', 'sequence' => 4, 'is_required' => true],
            ],
        ];

        foreach ($panelDefinitions as $serviceName => $items) {
            $profile = $this->findProfileByServiceName($serviceName);

            if ($profile === null) {
                continue;
            }

            $panel = $profile->ensurePanel();

            foreach ($items as $item) {
                $childProfile = $analyteProfiles[$item['analyte']] ?? null;

                if ($childProfile === null) {
                    continue;
                }

                DiagnosticPanelItem::query()->updateOrCreate(
                    [
                        'panel_id' => $panel->id,
                        'child_profile_id' => $childProfile->id,
                    ],
                    [
                        'sequence' => $item['sequence'],
                        'is_required' => $item['is_required'],
                    ]
                );
            }
        }
    }

    /**
     * @return array<string, DiagnosticServiceProfile>
     */
    protected function ensureAnalyteComponentProfiles(): array
    {
        $category = $this->ensureCategory(
            code: 'LAB',
            name: 'Laboratory',
            description: 'Blood tests, urinalysis, and diagnostic lab services',
            sortOrder: 3,
        );

        $analytes = [
            'hemoglobin' => ['name' => 'Hemoglobin', 'loinc_code' => '718-7', 'loinc_display' => 'Hemoglobin [Mass/volume] in Blood'],
            'pcv' => ['name' => 'PCV / Hematocrit', 'loinc_code' => '4544-3', 'loinc_display' => 'Hematocrit [Volume Fraction] of Blood'],
            'wbc' => ['name' => 'White Blood Cells', 'loinc_code' => '6690-2', 'loinc_display' => 'Leukocytes [#/volume] in Blood'],
            'platelets' => ['name' => 'Platelets', 'loinc_code' => '777-3', 'loinc_display' => 'Platelets [#/volume] in Blood'],
            'total_cholesterol' => ['name' => 'Total Cholesterol', 'loinc_code' => '2093-3', 'loinc_display' => 'Cholesterol [Mass/volume] in Serum or Plasma'],
            'hdl' => ['name' => 'HDL Cholesterol', 'loinc_code' => '2085-9', 'loinc_display' => 'Cholesterol in HDL [Mass/volume] in Serum or Plasma'],
            'ldl' => ['name' => 'LDL Cholesterol', 'loinc_code' => '13457-7', 'loinc_display' => 'Cholesterol in LDL [Mass/volume] in Serum or Plasma'],
            'triglycerides' => ['name' => 'Triglycerides', 'loinc_code' => '2571-8', 'loinc_display' => 'Triglyceride [Mass/volume] in Serum or Plasma'],
            'alt' => ['name' => 'ALT', 'loinc_code' => '1742-6', 'loinc_display' => 'Alanine aminotransferase [Enzymatic activity/volume] in Serum or Plasma'],
            'ast' => ['name' => 'AST', 'loinc_code' => '1920-8', 'loinc_display' => 'Aspartate aminotransferase [Enzymatic activity/volume] in Serum or Plasma'],
            'alp' => ['name' => 'ALP', 'loinc_code' => '6768-6', 'loinc_display' => 'Alkaline phosphatase [Enzymatic activity/volume] in Serum or Plasma'],
            'bilirubin_total' => ['name' => 'Total Bilirubin', 'loinc_code' => '1975-2', 'loinc_display' => 'Bilirubin.total [Mass/volume] in Serum or Plasma'],
        ];

        $profiles = [];

        foreach ($analytes as $key => $analyte) {
            $service = $this->findOrCreateService($category, [
                'name' => $analyte['name'],
                'description' => "Analyte component: {$analyte['name']}",
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => $analyte['loinc_code'],
                'loinc_display' => $analyte['loinc_display'],
                'service_defaults' => [
                    'price' => 0.00,
                    'is_billable' => false,
                    'is_active' => false,
                    'metadata' => [
                        'seed_source' => 'diagnostics_starter_catalog',
                        'component_only' => true,
                    ],
                ],
                'template' => [
                    'name' => "{$analyte['name']} Component Template",
                    'fields' => [
                        [
                            'field_key' => $key,
                            'label' => $analyte['name'],
                            'value_type' => 'numeric',
                            'observation_code' => $analyte['loinc_code'],
                            'observation_name' => $analyte['loinc_display'],
                        ],
                    ],
                ],
            ]);

            $profiles[$key] = $this->ensureProfile($service, [
                'discipline' => 'lab',
                'loinc_code' => $analyte['loinc_code'],
                'loinc_display' => $analyte['loinc_display'],
                'default_specimen_type' => 'blood',
            ]);
        }

        return $profiles;
    }

    protected function seedStarterReferenceRanges(): void
    {
        $hemoglobin = $this->findProfileByServiceName('Hemoglobin');
        $wbc = $this->findProfileByServiceName('White Blood Cells');
        $glucose = $this->findProfileByServiceName('Blood Glucose');

        if ($hemoglobin !== null) {
            $this->ensureReferenceRange($hemoglobin->id, [
                'gender' => 'male',
                'age_min_months' => 216,
                'min_value' => 13.5,
                'max_value' => 17.5,
                'units' => 'g/dL',
                'critical_low' => 7.0,
                'critical_high' => 20.0,
            ]);
            $this->ensureReferenceRange($hemoglobin->id, [
                'gender' => 'female',
                'age_min_months' => 216,
                'min_value' => 12.0,
                'max_value' => 15.5,
                'units' => 'g/dL',
                'critical_low' => 7.0,
                'critical_high' => 20.0,
            ]);
        }

        if ($wbc !== null) {
            $this->ensureReferenceRange($wbc->id, [
                'gender' => 'any',
                'age_min_months' => 216,
                'min_value' => 4.0,
                'max_value' => 11.0,
                'units' => '10*9/L',
                'critical_low' => 2.0,
                'critical_high' => 30.0,
            ]);
        }

        if ($glucose !== null) {
            $this->ensureReferenceRange($glucose->id, [
                'gender' => 'any',
                'age_min_months' => 216,
                'min_value' => 70,
                'max_value' => 99,
                'units' => 'mg/dL',
                'critical_low' => 40,
                'critical_high' => 400,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function ensureReferenceRange(string $profileId, array $attributes): DiagnosticReferenceRange
    {
        return DiagnosticReferenceRange::query()->updateOrCreate(
            [
                'profile_id' => $profileId,
                'gender' => $attributes['gender'],
                'age_min_months' => $attributes['age_min_months'] ?? null,
                'age_max_months' => $attributes['age_max_months'] ?? null,
            ],
            [
                'min_value' => $attributes['min_value'] ?? null,
                'max_value' => $attributes['max_value'] ?? null,
                'units' => $attributes['units'] ?? null,
                'critical_low' => $attributes['critical_low'] ?? null,
                'critical_high' => $attributes['critical_high'] ?? null,
            ]
        );
    }

    protected function findProfileByServiceName(string $serviceName): ?DiagnosticServiceProfile
    {
        $service = Service::query()->where('name', $serviceName)->first();

        if ($service === null) {
            return null;
        }

        return DiagnosticServiceProfile::query()
            ->where('service_id', $service->id)
            ->first();
    }

    /**
     * @return list<array{name: string, description: string, discipline: string, category_code: string, loinc_code: ?string, loinc_display: ?string, service_defaults?: array<string, mixed>, template: array{name: string, fields: list<array{field_key: string, label: string, value_type: string}>}}>
     */
    protected function starterCatalog(): array
    {
        return [
            [
                'name' => 'Full Blood Count (FBC)',
                'description' => 'Complete blood count test.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '58410-2',
                'loinc_display' => 'CBC panel - Blood',
                'template' => [
                    'name' => 'FBC Default Template',
                    'fields' => [
                        ['field_key' => 'hemoglobin', 'label' => 'Hemoglobin', 'value_type' => 'numeric', 'observation_code' => '718-7', 'default_units' => 'g/dL', 'is_required' => true, 'reference_range_low' => 12.0, 'reference_range_high' => 17.5],
                        ['field_key' => 'pcv', 'label' => 'PCV / Hematocrit', 'value_type' => 'numeric', 'observation_code' => '4544-3', 'default_units' => '%', 'is_required' => true],
                        ['field_key' => 'wbc', 'label' => 'White Blood Cells', 'value_type' => 'numeric', 'observation_code' => '6690-2', 'default_units' => '10*9/L', 'is_required' => true, 'reference_range_low' => 4.0, 'reference_range_high' => 11.0],
                        ['field_key' => 'platelets', 'label' => 'Platelets', 'value_type' => 'numeric', 'observation_code' => '777-3', 'default_units' => '10*9/L', 'is_required' => true],
                    ],
                ],
            ],
            [
                'name' => 'Urinalysis',
                'description' => 'Routine urine analysis.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '24357-6',
                'loinc_display' => 'Urinalysis complete',
                'template' => [
                    'name' => 'Urinalysis Default Template',
                    'fields' => [
                        ['field_key' => 'appearance', 'label' => 'Appearance', 'value_type' => 'text'],
                        ['field_key' => 'protein', 'label' => 'Protein', 'value_type' => 'text'],
                        ['field_key' => 'glucose', 'label' => 'Glucose', 'value_type' => 'text'],
                        ['field_key' => 'ketones', 'label' => 'Ketones', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Malaria Test (RDT)',
                'description' => 'Rapid malaria diagnostic test.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '85477-1',
                'loinc_display' => 'Plasmodium sp Ag [Presence] in Blood',
                'template' => [
                    'name' => 'Malaria Test Default Template',
                    'fields' => [
                        ['field_key' => 'result', 'label' => 'Result', 'value_type' => 'select'],
                        ['field_key' => 'species', 'label' => 'Species', 'value_type' => 'text'],
                        ['field_key' => 'parasite_density', 'label' => 'Parasite Density', 'value_type' => 'numeric'],
                    ],
                ],
            ],
            [
                'name' => 'Blood Glucose',
                'description' => 'Blood sugar level test.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '2345-7',
                'loinc_display' => 'Glucose [Mass/volume] in Serum or Plasma',
                'template' => [
                    'name' => 'Blood Glucose Default Template',
                    'fields' => [
                        ['field_key' => 'glucose', 'label' => 'Glucose', 'value_type' => 'numeric', 'observation_code' => '2345-7', 'default_units' => 'mg/dL', 'is_required' => true, 'reference_range_low' => 70, 'reference_range_high' => 99],
                        ['field_key' => 'sample_type', 'label' => 'Sample Type', 'value_type' => 'text'],
                        ['field_key' => 'fasting_status', 'label' => 'Fasting Status', 'value_type' => 'select'],
                    ],
                ],
            ],
            [
                'name' => 'Typhoid Test',
                'description' => 'Widal test for typhoid fever.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => null,
                'loinc_display' => null,
                'template' => [
                    'name' => 'Typhoid Default Template',
                    'fields' => [
                        ['field_key' => 'o_titre', 'label' => 'O Titre', 'value_type' => 'text'],
                        ['field_key' => 'h_titre', 'label' => 'H Titre', 'value_type' => 'text'],
                        ['field_key' => 'interpretation', 'label' => 'Interpretation', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Lipid Profile',
                'description' => 'Cholesterol and triglyceride panel.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '57698-3',
                'loinc_display' => 'Lipid panel with direct LDL',
                'service_defaults' => [
                    'price' => 65.00,
                    'estimated_duration_minutes' => 20,
                ],
                'template' => [
                    'name' => 'Lipid Profile Default Template',
                    'fields' => [
                        ['field_key' => 'total_cholesterol', 'label' => 'Total Cholesterol', 'value_type' => 'numeric'],
                        ['field_key' => 'hdl', 'label' => 'HDL', 'value_type' => 'numeric'],
                        ['field_key' => 'ldl', 'label' => 'LDL', 'value_type' => 'numeric'],
                        ['field_key' => 'triglycerides', 'label' => 'Triglycerides', 'value_type' => 'numeric'],
                    ],
                ],
            ],
            [
                'name' => 'Liver Function Test',
                'description' => 'Liver enzymes and bilirubin panel.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '24325-3',
                'loinc_display' => 'Hepatic function panel',
                'service_defaults' => [
                    'price' => 70.00,
                    'estimated_duration_minutes' => 20,
                ],
                'template' => [
                    'name' => 'LFT Default Template',
                    'fields' => [
                        ['field_key' => 'alt', 'label' => 'ALT', 'value_type' => 'numeric'],
                        ['field_key' => 'ast', 'label' => 'AST', 'value_type' => 'numeric'],
                        ['field_key' => 'alp', 'label' => 'ALP', 'value_type' => 'numeric'],
                        ['field_key' => 'bilirubin_total', 'label' => 'Total Bilirubin', 'value_type' => 'numeric'],
                    ],
                ],
            ],
            [
                'name' => 'Renal Function Test',
                'description' => 'Renal chemistry assessment.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => null,
                'loinc_display' => null,
                'service_defaults' => [
                    'price' => 70.00,
                    'estimated_duration_minutes' => 20,
                ],
                'template' => [
                    'name' => 'RFT Default Template',
                    'fields' => [
                        ['field_key' => 'urea', 'label' => 'Urea', 'value_type' => 'numeric'],
                        ['field_key' => 'creatinine', 'label' => 'Creatinine', 'value_type' => 'numeric'],
                        ['field_key' => 'egfr', 'label' => 'eGFR', 'value_type' => 'numeric'],
                    ],
                ],
            ],
            [
                'name' => 'Electrolytes / Urea / Creatinine',
                'description' => 'Routine kidney chemistry panel.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => null,
                'loinc_display' => null,
                'service_defaults' => [
                    'price' => 75.00,
                    'estimated_duration_minutes' => 20,
                ],
                'template' => [
                    'name' => 'EUC Default Template',
                    'fields' => [
                        ['field_key' => 'sodium', 'label' => 'Sodium', 'value_type' => 'numeric'],
                        ['field_key' => 'potassium', 'label' => 'Potassium', 'value_type' => 'numeric'],
                        ['field_key' => 'urea', 'label' => 'Urea', 'value_type' => 'numeric'],
                        ['field_key' => 'creatinine', 'label' => 'Creatinine', 'value_type' => 'numeric'],
                    ],
                ],
            ],
            [
                'name' => 'Pregnancy Test',
                'description' => 'Urine or serum beta-hCG screening.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '2106-3',
                'loinc_display' => 'Choriogonadotropin (pregnancy test) [Presence] in Urine',
                'service_defaults' => [
                    'price' => 20.00,
                    'estimated_duration_minutes' => 10,
                ],
                'template' => [
                    'name' => 'Pregnancy Test Default Template',
                    'fields' => [
                        ['field_key' => 'result', 'label' => 'Result', 'value_type' => 'select'],
                        ['field_key' => 'sample_type', 'label' => 'Sample Type', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'HIV Screening',
                'description' => 'HIV screening test.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '56888-1',
                'loinc_display' => 'HIV 1 and 2 Ab and HIV1 p24 Ag panel',
                'service_defaults' => [
                    'price' => 30.00,
                    'estimated_duration_minutes' => 15,
                ],
                'template' => [
                    'name' => 'HIV Screening Default Template',
                    'fields' => [
                        ['field_key' => 'result', 'label' => 'Result', 'value_type' => 'select'],
                        ['field_key' => 'test_method', 'label' => 'Test Method', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'HBsAg',
                'description' => 'Hepatitis B surface antigen screening.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '5195-3',
                'loinc_display' => 'Hepatitis B virus surface Ag [Presence] in Serum',
                'service_defaults' => [
                    'price' => 30.00,
                    'estimated_duration_minutes' => 15,
                ],
                'template' => [
                    'name' => 'HBsAg Default Template',
                    'fields' => [
                        ['field_key' => 'result', 'label' => 'Result', 'value_type' => 'select'],
                        ['field_key' => 'test_method', 'label' => 'Test Method', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'HCV Screening',
                'description' => 'Hepatitis C screening test.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '13955-0',
                'loinc_display' => 'Hepatitis C virus Ab [Presence] in Serum',
                'service_defaults' => [
                    'price' => 30.00,
                    'estimated_duration_minutes' => 15,
                ],
                'template' => [
                    'name' => 'HCV Screening Default Template',
                    'fields' => [
                        ['field_key' => 'result', 'label' => 'Result', 'value_type' => 'select'],
                        ['field_key' => 'test_method', 'label' => 'Test Method', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Stool Microscopy',
                'description' => 'Microscopy for ova, parasites, and stool characteristics.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => null,
                'loinc_display' => null,
                'service_defaults' => [
                    'price' => 25.00,
                    'estimated_duration_minutes' => 20,
                ],
                'template' => [
                    'name' => 'Stool Microscopy Default Template',
                    'fields' => [
                        ['field_key' => 'appearance', 'label' => 'Appearance', 'value_type' => 'text'],
                        ['field_key' => 'ova_parasites', 'label' => 'Ova / Parasites', 'value_type' => 'text'],
                        ['field_key' => 'occult_blood', 'label' => 'Occult Blood', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Microscopy, Culture and Sensitivity',
                'description' => 'Routine microscopy, culture, and sensitivity test.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => null,
                'loinc_display' => null,
                'service_defaults' => [
                    'price' => 55.00,
                    'estimated_duration_minutes' => 30,
                ],
                'template' => [
                    'name' => 'MCS Default Template',
                    'fields' => [
                        ['field_key' => 'specimen_source', 'label' => 'Specimen Source', 'value_type' => 'text'],
                        ['field_key' => 'microscopy', 'label' => 'Microscopy', 'value_type' => 'text'],
                        ['field_key' => 'organism', 'label' => 'Organism Isolated', 'value_type' => 'text'],
                        ['field_key' => 'sensitivity', 'label' => 'Sensitivity Pattern', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'HbA1c',
                'description' => 'Glycated hemoglobin test.',
                'discipline' => 'lab',
                'category_code' => 'LAB',
                'loinc_code' => '4548-4',
                'loinc_display' => 'Hemoglobin A1c/Hemoglobin.total in Blood',
                'service_defaults' => [
                    'price' => 45.00,
                    'estimated_duration_minutes' => 20,
                ],
                'template' => [
                    'name' => 'HbA1c Default Template',
                    'fields' => [
                        ['field_key' => 'hba1c', 'label' => 'HbA1c', 'value_type' => 'numeric'],
                        ['field_key' => 'estimated_average_glucose', 'label' => 'Estimated Average Glucose', 'value_type' => 'numeric'],
                    ],
                ],
            ],
            [
                'name' => 'Chest X-Ray',
                'description' => 'Standard chest X-ray examination.',
                'discipline' => 'radiology',
                'category_code' => 'RAD',
                'modality' => 'XR',
                'loinc_code' => '30745-4',
                'loinc_display' => 'XR Chest 2 Views',
                'template' => [
                    'name' => 'Chest X-Ray Report Template',
                    'fields' => [
                        ['field_key' => 'clinical_note', 'label' => 'Clinical Note', 'value_type' => 'text'],
                        ['field_key' => 'findings', 'label' => 'Findings', 'value_type' => 'text'],
                        ['field_key' => 'impression', 'label' => 'Impression', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Head CT Scan',
                'description' => 'Computed tomography of the head.',
                'discipline' => 'radiology',
                'category_code' => 'RAD',
                'modality' => 'CT',
                'loinc_code' => '24727-0',
                'loinc_display' => 'CT Head WO contrast',
                'template' => [
                    'name' => 'Head CT Report Template',
                    'fields' => [
                        ['field_key' => 'clinical_note', 'label' => 'Clinical Note', 'value_type' => 'text'],
                        ['field_key' => 'findings', 'label' => 'Findings', 'value_type' => 'text'],
                        ['field_key' => 'impression', 'label' => 'Impression', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Abdominal Ultrasound',
                'description' => 'Ultrasound examination of the abdomen.',
                'discipline' => 'radiology',
                'category_code' => 'RAD',
                'modality' => 'US',
                'loinc_code' => '30747-0',
                'loinc_display' => 'US Abdomen complete',
                'template' => [
                    'name' => 'Abdominal Ultrasound Template',
                    'fields' => [
                        ['field_key' => 'liver', 'label' => 'Liver', 'value_type' => 'text'],
                        ['field_key' => 'gall_bladder', 'label' => 'Gall Bladder', 'value_type' => 'text'],
                        ['field_key' => 'kidneys', 'label' => 'Kidneys', 'value_type' => 'text'],
                        ['field_key' => 'impression', 'label' => 'Impression', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'ECG (Electrocardiogram)',
                'description' => 'Heart rhythm and electrical activity test.',
                'discipline' => 'radiology',
                'category_code' => 'RAD',
                'modality' => 'ECG',
                'loinc_code' => '11524-6',
                'loinc_display' => 'EKG study',
                'template' => [
                    'name' => 'ECG Report Template',
                    'fields' => [
                        ['field_key' => 'rhythm', 'label' => 'Rhythm', 'value_type' => 'text'],
                        ['field_key' => 'rate', 'label' => 'Rate', 'value_type' => 'numeric'],
                        ['field_key' => 'axis', 'label' => 'Axis', 'value_type' => 'text'],
                        ['field_key' => 'impression', 'label' => 'Impression', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Pelvic Ultrasound',
                'description' => 'Ultrasound assessment of the pelvis.',
                'discipline' => 'radiology',
                'category_code' => 'RAD',
                'modality' => 'US',
                'loinc_code' => '30746-2',
                'loinc_display' => 'US Pelvis',
                'service_defaults' => [
                    'price' => 110.00,
                    'estimated_duration_minutes' => 20,
                ],
                'template' => [
                    'name' => 'Pelvic Ultrasound Template',
                    'fields' => [
                        ['field_key' => 'uterus', 'label' => 'Uterus', 'value_type' => 'text'],
                        ['field_key' => 'ovaries', 'label' => 'Ovaries', 'value_type' => 'text'],
                        ['field_key' => 'cul_de_sac', 'label' => 'Cul-de-sac', 'value_type' => 'text'],
                        ['field_key' => 'impression', 'label' => 'Impression', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Obstetric Ultrasound',
                'description' => 'Routine obstetric scan.',
                'discipline' => 'radiology',
                'category_code' => 'RAD',
                'modality' => 'US',
                'loinc_code' => '30748-8',
                'loinc_display' => 'US Obstetric',
                'service_defaults' => [
                    'price' => 130.00,
                    'estimated_duration_minutes' => 25,
                ],
                'template' => [
                    'name' => 'Obstetric Ultrasound Template',
                    'fields' => [
                        ['field_key' => 'gestational_age', 'label' => 'Gestational Age', 'value_type' => 'text'],
                        ['field_key' => 'fetal_heart_rate', 'label' => 'Fetal Heart Rate', 'value_type' => 'numeric'],
                        ['field_key' => 'placenta', 'label' => 'Placenta', 'value_type' => 'text'],
                        ['field_key' => 'impression', 'label' => 'Impression', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Histopathology',
                'description' => 'Histopathologic examination of submitted tissue.',
                'discipline' => 'pathology',
                'category_code' => 'PAT',
                'loinc_code' => '60568-3',
                'loinc_display' => 'Pathology study report',
                'service_defaults' => [
                    'price' => 150.00,
                    'estimated_duration_minutes' => 30,
                ],
                'template' => [
                    'name' => 'Histopathology Report Template',
                    'fields' => [
                        ['field_key' => 'specimen_description', 'label' => 'Specimen Description', 'value_type' => 'text'],
                        ['field_key' => 'gross_description', 'label' => 'Gross Description', 'value_type' => 'text'],
                        ['field_key' => 'microscopic_description', 'label' => 'Microscopic Description', 'value_type' => 'text'],
                        ['field_key' => 'diagnosis', 'label' => 'Diagnosis', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Cytology',
                'description' => 'Cytology examination and interpretation.',
                'discipline' => 'pathology',
                'category_code' => 'PAT',
                'loinc_code' => '33717-0',
                'loinc_display' => 'Cytology report',
                'service_defaults' => [
                    'price' => 120.00,
                    'estimated_duration_minutes' => 25,
                ],
                'template' => [
                    'name' => 'Cytology Report Template',
                    'fields' => [
                        ['field_key' => 'specimen_source', 'label' => 'Specimen Source', 'value_type' => 'text'],
                        ['field_key' => 'cellular_findings', 'label' => 'Cellular Findings', 'value_type' => 'text'],
                        ['field_key' => 'diagnosis', 'label' => 'Diagnosis', 'value_type' => 'text'],
                    ],
                ],
            ],
            [
                'name' => 'Biopsy Examination',
                'description' => 'Biopsy examination and pathology report.',
                'discipline' => 'pathology',
                'category_code' => 'PAT',
                'loinc_code' => '22634-0',
                'loinc_display' => 'Pathology report biopsy',
                'service_defaults' => [
                    'price' => 180.00,
                    'estimated_duration_minutes' => 30,
                ],
                'template' => [
                    'name' => 'Biopsy Examination Template',
                    'fields' => [
                        ['field_key' => 'site', 'label' => 'Biopsy Site', 'value_type' => 'text'],
                        ['field_key' => 'gross_description', 'label' => 'Gross Description', 'value_type' => 'text'],
                        ['field_key' => 'microscopy', 'label' => 'Microscopy', 'value_type' => 'text'],
                        ['field_key' => 'diagnosis', 'label' => 'Diagnosis', 'value_type' => 'text'],
                    ],
                ],
            ],
        ];
    }
}
