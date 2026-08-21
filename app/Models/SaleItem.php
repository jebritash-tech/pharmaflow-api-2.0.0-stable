<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    //
    protected $fillable = [
        'sale_id',
        'medicine_batch_id',
        'medicine_unit_id',
        'quantity',
        'unit',
        'quantity_pieces',
        'quantity_base',
        'price',
        'profit'
    ];

    public function batch()
    {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }
}
