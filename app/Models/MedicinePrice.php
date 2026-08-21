<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicinePrice extends Model
{
    protected $fillable = [
        'batch_id',

        'medicine_id',

        'unit_id',

        'buy_price',

        'sell_price',

        'profit_amount',

        'profit_percent',

        'is_active',

    ];

    protected $casts = [

        'buy_price'=>'decimal:2',

        'sell_price'=>'decimal:2',

        'profit_amount'=>'decimal:2',

        'profit_percent'=>'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    // public function medicine()
    // {
    //     return $this->belongsTo(
    //         Medicine::class
    //     );
    // }

    // public function batch()
    // {
    //     return $this->belongsTo(
    //         MedicineBatch::class
    //     );
    // }

    // public function unit()
    // {
    //     return $this->belongsTo(
    //         Unit::class
    //     );
    // }

    public function batch()
    {
        return $this->belongsTo(
            MedicineBatch::class,
            'batch_id'
        );
    }

    public function medicine()
    {
        return $this->belongsTo(
            Medicine::class,
            'medicine_id'
        );
    }

    public function unit()
    {
        return $this->belongsTo(
            MedicineUnit::class,
            'unit_id'
        );
    }
}