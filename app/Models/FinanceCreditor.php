<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceCreditor extends Model
{
    protected $fillable = [
        'name',
        'account_code',
        'creditorable_id',
        'creditorable_type',
    ];

    protected $casts = [
        'creditorable_type' => 'string',
    ];

    public function creditorable()
    {
        return $this->morphTo();
    }
}
?>

