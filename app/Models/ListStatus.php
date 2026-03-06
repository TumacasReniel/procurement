<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListStatus extends Model
{
    use HasFactory;
    public static function getID($status_name, $classification)
    {
        $status = self::where('name', $status_name)->where('classification', $classification)->first();
        return $status ? $status->id : null;
    }
}
