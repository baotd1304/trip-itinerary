<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hoặc: $this->user()->can('export', Trip::class)
    }

    public function rules(): array
    {
        return [
            'month'  => ['required', 'date_format:Y-m'],
            'car_id' => ['required', 'integer', 'exists:cars,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'month.required'  => 'Vui lòng chọn tháng cần xuất.',
            'month.date_format' => 'Tháng không hợp lệ (định dạng YYYY-MM).',
            'car_id.required' => 'Vui lòng chọn xe.',
            'car_id.exists'   => 'Xe không tồn tại.',
        ];
    }
}