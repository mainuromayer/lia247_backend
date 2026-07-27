<?php

namespace App\Http\Requests\V1\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BusinessSetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:204800',
            'business_name' => 'required|string|max:255',
            'business_type' => 'nullable|string|max:255',
            'business_description' => 'nullable|string',
            'business_phone' => 'required|string|max:20',
            'business_address' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'opening_time' => 'required|string|date_format:H:i',
            'closing_time' => 'required|string|date_format:H:i',
            'license_number' => 'required|string|max:100',
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
