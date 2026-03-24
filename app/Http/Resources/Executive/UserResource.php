<?php

namespace App\Http\Resources\Executive;

use Hashids\Hashids;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hashids = new Hashids('krad',10);
        $code = $hashids->encode($this->id);

        return [
            'code' => $code,
            'avatar' => $this->profile?->avatar,
            'name' => $this->profile->name,
            'fullname' => $this->profile->fullname,
            'mobile' => $this->profile->mobile,
            'email' => $this->email,
            'username' => $this->username,
            'roles' => RoleResource::collection($this->myroles),
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'email_verified_at' => $this->email_verified_at,
            'two_factor_confirmed_at' => $this->two_factor_confirmed_at,
            'password_changed_at' => $this->password_changed_at,
            'created_at' => $this->created_at
        ];
    }
}
