<?php

namespace App\Http\Requests\V1\Vendor\Item;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ItemStoreRequest extends FormRequest
{
    use ApiResponse;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'cooking_time' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:204800',
            'price' => 'required|numeric|min:0',

            // Variants
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string|max:100',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.is_featured' => 'nullable|in:1,0,true,false,"true","false"',

            // Toppings
            'toppings' => 'nullable|array',
            'toppings.*.name' => 'required|string|max:100',
            'toppings.*.price' => 'required|numeric|min:0',
            'toppings.*.is_featured' => 'nullable|in:1,0,true,false,"true","false"',


        ];
    }

    public function messages(): array
    {
        return [

            'image.image' => 'Please upload a valid image file.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->error([], $validator->errors()->first(), 422));
    }
}