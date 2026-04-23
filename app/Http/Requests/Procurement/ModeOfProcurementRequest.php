<?php

namespace App\Http\Requests\Procurement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModeOfProcurementRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $modeId = $this->resolveModeId();

        return [
            'type' => ['nullable', 'string', 'max:100'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('list_dropdowns', 'name')
                    ->ignore($modeId)
                    ->where(fn ($query) => $query->whereIn('classification', [
                        'mode_of_procurement',
                        'modes_of_procurement',
                        'Mode of Procurement',
                    ])),
            ],
            'others' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get the validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the mode of procurement.',
            'name.unique' => 'This mode of procurement already exists.',
            'type.max' => 'The IRR section reference must not exceed 100 characters.',
            'others.max' => 'The legal basis / notes must not exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'IRR section reference',
            'name' => 'mode of procurement',
            'others' => 'legal basis / notes',
            'is_active' => 'status',
        ];
    }

    protected function resolveModeId(): mixed
    {
        $routeMode = $this->route('mode_of_procurement')
            ?? $this->route('modes_of_procurement')
            ?? $this->route('modes-of-procurement')
            ?? $this->input('id');

        return is_object($routeMode) ? $routeMode->id : $routeMode;
    }
}
