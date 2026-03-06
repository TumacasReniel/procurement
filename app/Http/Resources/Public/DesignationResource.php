<?php

namespace App\Http\Resources\Public;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'avatar' => ($this?->user?->profile && $this?->user?->profile->avatar && $this?->user?->profile->avatar !== 'noavatar.jpg')
            ? asset('storage/' .$this?->user->profile->avatar) 
            : asset('images/avatars/avatar.jpg'), 
            'oic_avatar' => ($this?->oic?->profile && $this?->oic?->profile->avatar && $this?->oic?->profile->avatar !== 'noavatar.jpg')
            ? asset('storage/' .$this?->oic->profile->avatar) 
            : asset('images/avatars/avatar.jpg'), 
            'order' => $this->order,
            'designation' => $this->designation->name,
            'assigned' => $this->assigned,
            'user' => new ProfileResource($this->user),
            'oic' => new ProfileResource($this->oic),
            'is_oic' => $this->is_oic,
            'is_active' => $this->is_active,
            'signatory' => new SignatoryResource($this->designationable),
            'updated_at' => $this->updated_at
        ];
    }
}
