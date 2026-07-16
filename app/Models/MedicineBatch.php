<?php

namespace App\Models;

use App\Models\Medicine;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Model;

class MedicineBatch extends Model
{
   
    protected $fillable = [
        'medicine_id', 
        'batch_number',
        'branch_id' ,
        'expiry_date', 
        'quantity', 
        'cost_price', 
        'selling_price'
    ];
    //
    public function medicine() {
        return $this->belongsTo(Medicine::class);
    }

    public function saleItems() {
        return $this->hasMany(SaleItem::class);
    }
}
