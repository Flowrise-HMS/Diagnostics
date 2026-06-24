<?php

namespace Modules\Diagnostics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiagnosticResultTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $templateId = $this->route('template')?->id;

        return [
            'profile_id' => ['required', 'uuid', 'exists:diagnostic_service_profiles,id'],
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'fields' => ['nullable', 'array'],
            'fields.*.field_key' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.label' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.value_type' => ['required_with:fields', 'string', Rule::in(['numeric', 'text', 'select'])],
            'fields.*.observation_code' => ['nullable', 'string', 'max:255'],
            'fields.*.observation_name' => ['nullable', 'string', 'max:255'],
            'fields.*.data_type' => ['nullable', 'string', 'max:50'],
            'fields.*.default_units' => ['nullable', 'string', 'max:50'],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.reference_range_low' => ['nullable', 'numeric'],
            'fields.*.reference_range_high' => ['nullable', 'numeric'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'fields.*.options' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_id.required' => 'Service profile is required.',
            'name.required' => 'Template name is required.',
            'fields.*.field_key.required_with' => 'Each field requires a field key.',
            'fields.*.label.required_with' => 'Each field requires a label.',
            'fields.*.value_type.required_with' => 'Each field requires a value type.',
        ];
    }
}
