<?php

namespace App\Http\Requests\V1\Vendor\Category;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;

class CategoryUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $slug = $this->route('slug');
        $category = Category::where('slug', $slug)->first();
        $categoryId = $category ? $category->id : null;

        return [
            // 'sometimes' means it will only validate if the field is present,
            // but for safety 'required' might be better if we always expect a name. 
            // We'll leave it as sometimes to match original but add proper unique rules.
            'name' => [
                'sometimes', 
                'string', 
                'max:255',
                Rule::unique('categories')->ignore($categoryId)->where(function ($query) use ($category) {
                    return $query->where('user_id', $category->user_id ?? Auth::id());
                })
            ],
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