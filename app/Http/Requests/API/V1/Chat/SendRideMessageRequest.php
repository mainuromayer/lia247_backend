<?php

namespace App\Http\Requests\API\V1\Chat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendRideMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ride_id' => 'required|exists:rides,id',
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required_without:files|string|nullable',
            'files' => 'nullable|array',
            'files.*' => 'file|max:51200',
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
