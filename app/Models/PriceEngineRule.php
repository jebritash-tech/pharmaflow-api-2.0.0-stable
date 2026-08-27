<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceEngineRule extends Model
{
    protected $fillable = [
        'name',
        'type',
        'apply_on',
        'value',
        'sort_order',
        'is_active',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function medicines()
    {
        return $this->hasMany(
            Medicine::class,
            'pricing_rule_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function roundingSettings(): array
    {
        $rounding = data_get(
            $this->settings,
            'rounding',
            []
        );

        return [

            'mode' => $rounding['mode'] ?? 'none',

            'unit' => max(

                1,

                (float) ($rounding['unit'] ?? 1)

            ),

        ];
    }
}