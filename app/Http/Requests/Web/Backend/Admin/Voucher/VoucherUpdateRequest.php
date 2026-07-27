<?php

namespace App\Http\Requests\Web\Backend\Admin\Voucher;

use Illuminate\Foundation\Http\FormRequest;

class VoucherUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'code' => 'required|string|max:50|unique:vouchers,code,' . $id,
            'type' => 'required|in:food,ride',
            'discount_type' => 'required|in:percentage,fixed,buy_one_get_one',
            'discount_amount' => 'required_unless:discount_type,buy_one_get_one|nullable|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
