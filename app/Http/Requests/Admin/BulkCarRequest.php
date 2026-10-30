<?php

namespace App\Http\Requests\Admin;

use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;

class BulkCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Car::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:activate,deactivate,delete'],
            'car_ids' => ['required', 'array', 'min:1', 'max:100'],
            'car_ids.*' => ['required', 'integer', 'distinct', 'exists:cars,id'],
        ];
    }
}
