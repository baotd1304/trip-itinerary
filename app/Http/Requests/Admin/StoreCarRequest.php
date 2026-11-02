<?php

namespace App\Http\Requests\Admin;

use App\Models\Car;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Car::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'license_plate' => ['required', 'string', 'max:20', Rule::unique((new Car)->getTable(), 'license_plate')],
            'owner' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
