<?php

namespace App\Http\Requests\V1\Customer\Cart;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Auth\Access\AuthorizationException;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_item_id' => 'required|exists:cart_items,id',
            'item_id' => 'nullable|exists:items,id',
            'variant_id' => 'nullable|exists:item_variants,id',
            'topping_ids' => 'nullable|array',
            'topping_ids.*' => 'exists:item_toppings,id',
            'quantity' => 'required|integer|min:1',
            'special_instruction' => 'nullable|string|max:500',
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
