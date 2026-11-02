<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('expense')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'overkm_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'overtime_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'overnight_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'holiday_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => [
                'sometimes',
                Rule::prohibitedIf(
                    $this->user()?->cannot('toggleActive', $this->route('expense')) ?? true,
                ),
                'boolean',
            ],
        ];
    }
}
