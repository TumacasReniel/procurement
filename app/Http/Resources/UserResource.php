<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => ($this->profile && $this->profile->avatar && $this->profile->avatar !== 'noavatar.jpg')
            ? asset('storage/' . $this->profile->avatar) 
            : asset('images/avatars/avatar.jpg'), 
            'avatar_name' => $this->profile->avatar,
            'name' => $this->profile->firstname.' '.$this->profile->lastname,
            'firstname' => $this->profile->firstname,
            'lastname' => $this->profile->lastname,
            'middlename' => $this->profile->middlename,
            'sex' => $this->profile->sex,
            'religion' => $this->profile->religion,
            'blood' => $this->profile->blood,
            'marital' => $this->profile->marital,
            'suffix' => $this->profile->suffix,
            'mobile' => $this->profile->mobile,
            'birthdate' => $this->profile->birthdate,
            'profile_id' => $this->profile->id,
            'position' => $this->organization->position->name,
            'signatory' => $this->signatory,
            'is_active' => $this->is_active,
            'must_change' => $this->must_change,
            'two_factor_enabled' => ($this->two_factor_secret) ? true : false,
            'two_factor_confirmed' => ($this->two_factor_confirmed_at) ? true : false,
            'password_changed_at' => $this->password_changed_at,
            'password_confirmed_at' => session('auth'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
