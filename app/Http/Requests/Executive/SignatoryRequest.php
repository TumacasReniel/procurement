<?php

namespace App\Http\Requests\Executive;

use App\Rules\NoOverlappingSchedule;
use App\Rules\NoOverlappingSchedule2;
use Illuminate\Foundation\Http\FormRequest;

class SignatoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        switch($this->option){
            case 'signatory':
                return [
                    'signatory_id' => ['required', 'exists:org_signatories,id'],
                    'user_id' => ['required', 'exists:users,id'],
                    'start_at' => [
                        'required',
                        'date',
                        new NoOverlappingSchedule(
                            $this->signatory_id,
                            $this->start_at,
                            $this->end_at
                        ),
                    ],
                    'end_at' => ['required', 'date', 'after_or_equal:start_at'],
                ];
            break;
            case 'designate':
                $rules = [
                    'signatory_id' => ['required', 'exists:org_signatories,id'],
                    'user_id' => ['required', 'exists:users,id'],
                    'is_oic' => ['required', 'boolean'],
                ];

                if ($this->boolean('is_oic')) {
                    $rules['start_at'] = [
                        'required',
                        'date',
                        new NoOverlappingSchedule2(
                            $this->signatory_id,
                            $this->start_at,
                            $this->end_at
                        ),
                    ];

                    $rules['end_at'] = [
                        'required',
                        'date',
                        'after_or_equal:start_at',
                    ];
                }
                return $rules;
            break;
            case 'create_bac_member':
                return [
                    'user_id' => ['required', 'exists:users,id'],
                ];
            break;
            case 'create_iar_member':
                return [
                    'user_id' => ['required', 'exists:users,id'],
                ];
            break;
        }
    }

    public function messages(): array
    {
        return [
            'signatory_id.required' => 'Please select a signatory.',
            'user_id.required' => 'Please select a user.',
            'user_id.exists' => 'The selected user is invalid.',
            'start_at.required' => 'Please set the start date.',
            'end_at.required' => 'Please set the end date.',
            'end_at.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }
}
