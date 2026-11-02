<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;

class BulkExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Expense::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:activate,deactivate,delete'],
            'expense_ids' => ['required', 'array', 'min:1', 'max:100'],
            'expense_ids.*' => ['required', 'integer', 'distinct', 'exists:expenses,id'],
        ];
    }
}
