<?php

namespace Modules\Diagnostics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'fields.*.name' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.field_type' => ['required_with:fields', 'string', 'max:50'],
            'fields.*.unit' => ['nullable', 'string', 'max:50'],
            'fields.*.reference_range_low' => ['nullable', 'string', 'max:50'],
            'fields.*.reference_range_high' => ['nullable', 'string', 'max:50'],
            'fields.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_id.required' => 'Service profile is required.',
            'name.required' => 'Template name is required.',
        ];
    }
}
