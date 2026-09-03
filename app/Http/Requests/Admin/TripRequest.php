<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TripRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'advisor'        => ['required', 'string', 'max:255'],
            'driver'         => ['required', 'string', 'max:255'],
            'car_id'         => ['required', 'exists:cars,id'],
            'day'            => ['required', 'date'],
            'origin'         => ['required', 'string', 'max:255'],
            'destination'    => ['required', 'string', 'max:255'],
            'departure_time' => ['required'],
            'arrival_time'   => ['required'],
            'odo_start'      => ['required', 'integer', 'min:0'],
            'odo_end'        => ['required', 'integer', 'gte:odo_start'],
            'overtime'       => ['nullable', 'integer', 'min:0', 'max:4'],
            'toll_fee'       => ['nullable', 'numeric', 'min:0'],
            'airport_fee'    => ['nullable', 'numeric', 'min:0'],
            'is_overnight'   => ['boolean'],
            'is_holiday'     => ['boolean'],
            'note'           => ['nullable', 'string', 'max:1000'],

            // ---- ảnh ----
            'images'              => ['nullable', 'array', 'max:10'],
            'images.*.public_id'  => ['required', 'string', 'max:255'],
            'images.*.url'        => ['required', 'url', 'max:500', 'starts_with:https://res.cloudinary.com/'],
            'images.*.format'     => ['nullable', 'string', 'max:20'],
            'images.*.width'      => ['nullable', 'integer'],
            'images.*.height'     => ['nullable', 'integer'],
            'images.*.bytes'      => ['nullable', 'integer'],

            'removed_image_ids'   => ['nullable', 'array'],
            'removed_image_ids.*' => ['integer', Rule::exists('trip_images', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_overnight' => $this->boolean('is_overnight'),
            'is_holiday'   => $this->boolean('is_holiday'),
        ]);
    }
}