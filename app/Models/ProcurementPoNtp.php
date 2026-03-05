<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcurementPoNtp extends Model
{
    protected $fillable = [
        'code',
        'po_id',
        'created_by_id',
        'approved_by_id',
        'updated_by_id',
        'status_id',
        'procurement_id',

    ];

    public function po()
    {
        return $this->belongsTo('App\Models\procurementNoaPo', 'po_id' );
    }

      
    public function status()
    {
        return $this->belongsTo('App\Models\ListStatus', 'status_id');
    }


    
    public static function generateNTPNumber($date = null)
    {
        if ($date) {
            $year = date("y", strtotime($date));  // 'y' gives the last two digits of the year
            $month = date("m", strtotime($date));
        } else {
            $year = date("y", strtotime("now"));  // 'y' gives the last two digits of the year
            $month = date("m", strtotime("now"));
        }
    
        $count = self::whereYear('created_at', date("Y", strtotime($date ?? "now")))
                     ->whereMonth('created_at', $month)
                     ->count() + 1;
    
        return 'NTP-' .$year . '-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
