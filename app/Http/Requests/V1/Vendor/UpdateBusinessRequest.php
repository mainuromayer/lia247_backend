<?php

namespace App\Http\Requests\V1\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateBusinessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $business = Auth::user()->business;

        return [
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:204800',
            'business_name' => 'sometimes|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'business_description' => 'nullable|string',
            'business_phone' => 'sometimes|string|max:20',
            'business_address' => 'sometimes|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'opening_time' => 'sometimes|string|date_format:H:i',
            'closing_time' => 'sometimes|string|date_format:H:i',
            'license_number' => [
                'sometimes', 
                'string', 
                'max:100',
                // Rule::unique('businesses')->ignore($business->id ?? null)
            ],
            'free_delivery' => 'sometimes|boolean',
            'free_delivery_type' => 'sometimes|in:admin,vendor,hybrid',
            'admin_contribution' => 'sometimes|numeric|min:0',
            'vendor_contribution' => 'sometimes|numeric|min:0',
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
