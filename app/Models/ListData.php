<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListData extends Model
{
    use HasFactory;
    public static function getID($name)
    {
        $data = self::where('name', $name)->first();
        return $data ? $data->id : null;
    }

}
