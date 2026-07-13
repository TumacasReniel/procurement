<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotModule extends Model
{
    protected $fillable = [
        'module_key',
        'label',
        'description',
        'icon',
        'table_name',
        'model_class',
        'is_enabled',
        'intent_phrases',
        'intent_weight',
        'eager_loads',
        'display_columns',
        'searchable_columns',
        'searchable_relations',
        'status_relation',
        'order_column',
    ];

    protected $casts = [
        'is_enabled'           => 'boolean',
        'intent_phrases'       => 'array',
        'intent_weight'        => 'integer',
        'eager_loads'          => 'array',
        'display_columns'      => 'array',
        'searchable_columns'   => 'array',
        'searchable_relations' => 'array',
    ];

    public static function enabled(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_enabled', true)->orderBy('label')->get();
    }

    public static function all_modules(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('label')->get();
    }
}
