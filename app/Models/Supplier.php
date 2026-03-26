<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
        'user_id',

    ];

    public function created_by()
    {
        return $this->belongsTo('App\Models\User', 'user_id')->with('profile');
    }

    public function address()
    {
        return $this->hasOne('App\Models\SupplierAddress', 'supplier_id');
    }

    public function attachments()
    {
        return $this->hasMany('App\Models\SupplierAttachment', 'supplier_id');
    }

    public function conformes()
    {
        return $this->hasMany('App\Models\SupplierConforme', 'supplier_id');
    }

    public static function generateCode($date = null)
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
    
        return 'SUP-' .$year . '-' . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

}
