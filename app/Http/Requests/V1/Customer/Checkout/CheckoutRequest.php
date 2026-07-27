<?php

namespace App\Http\Requests\V1\Customer\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_type'        => 'required|in:delivery,pickup',
            'delivery_option_id'=> 'nullable|exists:delivery_options,id',
            'receiver_name'     => 'required_if:order_type,pickup|string|max:255',
            'receiver_phone'    => 'required_if:order_type,pickup|string|max:50',
            'address'           => 'nullable|string|max:500',
            'latitude'          => 'nullable|string|max:100',
            'longitude'         => 'nullable|string|max:100',
            'voucher_id'        => 'nullable|exists:vouchers,id',
            'payment_type'      => 'required|in:wallet,stripe',
            'payment_method_id' => 'required_if:payment_type,stripe|string',
            'notes'             => 'nullable|string|max:1000',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation errors',
            'data' => $validator->errors(),
            'code' => 422,
        ], 422));
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Unauthorized access',
            'data' => [],
            'code' => 403,
        ], 403));
    }
}
