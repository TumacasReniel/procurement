<?php

namespace App\Http\Requests\Inventory\Concerns;

trait AuthorizesInventoryAccess
{
    public function authorize(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->hasAnyRole([
            'Administrator',
            'Supply Officer',
            'Supply Staff',
        ]);
    }
}
