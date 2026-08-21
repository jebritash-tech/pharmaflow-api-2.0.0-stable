<?php

namespace App\Models;
use App\Models\Purchase;
use App\Models\MedicineBatch;
use App\Models\Medicine;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
   

    protected $fillable = [

        'purchase_id',

        'medicine_id',

        'unit_id',

        'quantity',

        'factor',

        'base_quantity',

        'buy_price',

        'subtotal'

    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function unit()
    {
        return $this->belongsTo(MedicineUnit::class,'unit_id');
    }

    public function batch()
    {
        return $this->hasOne(
            MedicineBatch::class,
            'purchase_item_id'
        );
    }

}