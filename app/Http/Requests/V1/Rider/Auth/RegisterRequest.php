<?php

namespace App\Http\Requests\V1\Rider\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:204800',
            'name' => 'required|string|max:250',
            'email' => 'required|email|unique:users,email',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'nid' => 'required|string',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|date_format:Y-m-d',
            'vehicle_photos' => 'required|array|min:1',
            'vehicle_photos.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:204800',
            'license_front' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:204800',
            'license_back' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:204800',
            'vehicle_type' => 'required|string',
            'vehicle_model' => 'required|string',
            'vehicle_color' => 'required|string',
            'insurance_number' => 'required|string',
            'manufacture_year' => 'required|string',
            'max_occupancy' => 'required|integer',
            'license_number' => 'required|string',
            'driving_license' => 'required|string',
            'driving_experience' => 'required|string',
            'police_reports' => 'required|array|min:1',
            'police_reports.*' => 'file|image|mimes:jpg,jpeg,png,webp|max:204800',
            'front_prfile_selfie' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:204800',
            'side_profile_selfie' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:204800',
            'terms_and_conditions' => 'required|accepted',
        ];
    }
}
