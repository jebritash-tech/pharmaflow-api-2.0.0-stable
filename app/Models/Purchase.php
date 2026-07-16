<?php

namespace App\Models;
use App\Models\Supplier;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    //
    protected $fillable = ['supplier_id', 'total_amount'];
    
    // supplier
    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }
    // items purchased
    public function items() {
        return $this->hasMany(PurchaseItem::class);
    }
}
