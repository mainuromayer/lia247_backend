<?php

namespace App\Http\Requests\Web\Backend\Admin\OrderSetting;

use Illuminate\Foundation\Http\FormRequest;

class OrderSettingUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'food_commission' => 'required|numeric|min:0|max:100',
            'ride_commission' => 'required|numeric|min:0|max:100',
            'pickup_estimated_time' => 'nullable|string|max:255',
        ];
    }
}
