<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class procurementPoIar extends Model
{
    protected $fillable = [
        'procurement_id',
        'po_id',
        'code',
    ];

    public function procurement()
    {
        return $this->belongsTo('App\Models\Procurement', 'procurement_id');
    }

    public function po()
    {
        return $this->belongsTo('App\Models\ProcurementNoaPo', 'po_id')->with('noa');
    }

    public static function generateIARNumber()
    {
        $now = now(); // Laravel's Carbon instance
        $year = $now->format('y'); // Last two digits of year
        $month = $now->format('m');

        // Count existing RFQs for this year and month
        $count = self::whereYear('created_at', $now->year)
                    ->whereMonth('created_at', $month)
                    ->count() + 1;

        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);

        return "IAR-{$year}-{$month}-{$sequence}";
    }
}
