<?php

namespace App\Http\Requests\API\V1\Customer\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class ReviewStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'vendor_rating' => 'required|integer|min:1|max:5',
            'vendor_comment' => 'nullable|string|max:1000',
            'delivery_man_rating' => 'required|integer|min:1|max:5',
            'delivery_man_comment' => 'nullable|string|max:1000',
            // 'rider_rating' => 'required|integer|min:1|max:5',
            // 'rider_comment' => 'nullable|string|max:1000',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'message' => 'Validation errors',
            'data' => $validator->errors(),
            'code' => 422
        ], 422));
    }
}