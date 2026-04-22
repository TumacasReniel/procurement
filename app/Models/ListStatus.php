<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListStatus extends Model
{
    use HasFactory;

    protected static function displayAliases(): array
    {
        return [
            'Items Delivered' => 'Items Delivered',
            'PO Items Delivered' => 'PO Items Delivered',
            'Partially Items Delivered' => 'Items Partially Delivered',
            'PO Partially Items Delivered' => 'PO Items Partially Delivered',
        ];
    }

    protected static function databaseAliases(): array
    {
        return array_flip(static::displayAliases());
    }

    public static function getID($status_name, $classification)
    {
        $resolvedName = static::databaseAliases()[$status_name] ?? $status_name;
        $status = self::where('name', $resolvedName)->where('classification', $classification)->first();
        return $status ? $status->id : null;
    }

    public function getNameAttribute($value)
    {
        return static::displayAliases()[$value] ?? $value;
    }
}
