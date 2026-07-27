<?php

namespace App\Http\Requests\Web\Backend\Admin\Settlement;

use Illuminate\Foundation\Http\FormRequest;

class SettlementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01'
        ];
    }
}
