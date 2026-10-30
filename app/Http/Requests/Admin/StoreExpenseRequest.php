<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Expense::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'overkm_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'overnight_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'holiday_rate' => ['nullable', 'numeric', 'min:0', 'max:999999999999999'],
            'note' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
