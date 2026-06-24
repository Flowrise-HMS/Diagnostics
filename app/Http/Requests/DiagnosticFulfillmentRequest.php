<?php

namespace Modules\Diagnostics\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Diagnostics\Enums\DiagnosticDiscipline;
use Modules\Diagnostics\Enums\FulfillmentStatus;

class DiagnosticFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_item_id' => ['required', 'uuid', 'exists:request_items,id'],
            'branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'discipline' => ['required', Rule::enum(DiagnosticDiscipline::class)],
            'status' => ['nullable', Rule::enum(FulfillmentStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'request_item_id.required' => 'Request item is required.',
            'branch_id.required' => 'Branch is required.',
            'discipline.required' => 'Discipline is required.',
        ];
    }
}
