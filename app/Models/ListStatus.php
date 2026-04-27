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
            'Delivered/For Inspection' => 'Items Delivered',
            'PO Delivered/For Inspection' => 'PO Items Delivered',
            'Partially Delivered / For Inspection' => 'Items Partially Delivered',
            'PO Partially Delivered/For Inspection' => 'PO Items Partially Delivered',
            'Items Delivered' => 'Items Delivered',
            'PO Items Delivered' => 'PO Items Delivered',
            'Partially Items Delivered' => 'Items Partially Delivered',
            'PO Partially Items Delivered' => 'PO Items Partially Delivered',
        ];
    }

    protected static function lookupCandidates($statusName): array
    {
        $normalized = trim((string) $statusName);

        $aliasMap = [
            'Items Delivered' => [
                'Delivered/For Inspection',
                'Items Delivered',
            ],
            'PO Items Delivered' => [
                'PO Delivered/For Inspection',
                'PO Items Delivered',
            ],
            'Items Partially Delivered' => [
                'Partially Delivered / For Inspection',
                'Partially Items Delivered',
                'Items Partially Delivered',
            ],
            'PO Items Partially Delivered' => [
                'PO Partially Delivered/For Inspection',
                'PO Partially Items Delivered',
                'PO Items Partially Delivered',
            ],
        ];

        return array_values(array_unique([
            ...($aliasMap[$normalized] ?? []),
            $normalized,
        ]));
    }

    public static function getID($status_name, $classification)
    {
        foreach (static::lookupCandidates($status_name) as $candidate) {
            $status = self::where('name', $candidate)
                ->where('classification', $classification)
                ->first();

            if ($status) {
                return $status->id;
            }
        }

        return null;
    }

    public function getNameAttribute($value)
    {
        return static::displayAliases()[$value] ?? $value;
    }
}
