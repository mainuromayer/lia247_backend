<?php

namespace App\Http\Requests\API\V1\Customer\Ride;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RideStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pickup_address' => 'required|string|max:500',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'dropoff_address' => 'required|string|max:500',
            'dropoff_lat' => 'required|numeric',
            'dropoff_lng' => 'required|numeric',
            'distance' => 'required|numeric|min:0.1',
            'estimated_fare' => 'required|numeric|min:0',
            'transport_type_id' => 'required|exists:transport_types,id',
            'payment_type' => 'required|in:wallet,stripe',
            'payment_method_id' => 'required_if:payment_type,stripe|string',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
