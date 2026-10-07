<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkTripReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['confirm', 'reject'])],
            'trip_ids' => ['required', 'array', 'min:1'],
            'trip_ids.*' => ['required', 'integer', 'distinct', Rule::exists('trips', 'id')],
            'reject_reason' => [
                Rule::requiredIf($this->input('action') === 'reject'),
                'nullable',
                'string',
                'min:5',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reject_reason.required' => 'Vui lòng nêu lý do từ chối để tài xế biết cần sửa gì.',
        ];
    }
}
