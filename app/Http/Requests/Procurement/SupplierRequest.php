<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|unqiue:suppliers,name,'.$this->id,
        ];

    }

    public function messages()
    {
       return [
            'name.required' => 'This field is required',
        ];
    }
}
