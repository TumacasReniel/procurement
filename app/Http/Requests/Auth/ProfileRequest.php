<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sex_id' => 'sometimes|required',
            'birthdate' => 'sometimes|required',
            'mobile' => 'sometimes|required|numeric|digits:11|unique:user_profiles,mobile,'.$this->profile_id,
            'marital_id' => 'sometimes|required',
            'religion_id' => 'sometimes|required',
            'blood_id' => 'sometimes|required',
            'is_same'       => 'sometimes|nullable|boolean',

            // Permanent Address
            'permanent.address'           => 'required|string|max:200',
            'permanent.region_code'       => 'required',
            'permanent.province_code'     => 'required',
            'permanent.municipality_code' => 'required',
            'permanent.barangay_code'     => 'required',
            'permanent.latitude'          => 'nullable|numeric',
            'permanent.longitude'         => 'nullable|numeric',

            // Home Address
            'home.address'           => 'required_if:is_same,0',
            'home.region_code'       => 'required_if:is_same,0',
            'home.province_code'     => 'required_if:is_same,0',
            'home.municipality_code' => 'required_if:is_same,0',
            'home.barangay_code'     => 'required_if:is_same,0',
            'home.latitude'          => 'nullable|numeric',
            'home.longitude'         => 'nullable|numeric',
        ];
    }

    public function messages()
    {
        return [
            'permanent.address.required' => 'Permanent address is required.',
            'home.address.required'      => 'Home address is required.',
        ];
    }
}
