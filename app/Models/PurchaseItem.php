<?php

namespace App\Models;
use App\Models\Purchase;
use App\Models\MedicineBatch;
use App\Models\Medicine;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    //
    protected $fillable = ['purchase_id', 'medicine_batch_id', 'quantity', 'price'];

    // has purchase
    public function purchase() {
        return $this->belongsTo(Purchase::class);
    }
    // has batch
    public function batch() {
        return $this->belongsTo(MedicineBatch::class, 'medicine_batch_id');
    }
    public function medicine()
    {
        // نستخدم hasOneThrough للوصول للدواء مباشرة من عنصر الفاتورة
        return $this->hasOneThrough(
            Medicine::class, 
            MedicineBatch::class, 
            'id',          // المفتاح الأجنبي في MedicineBatch
            'id',          // المفتاح الأساسي في Medicine
            'medicine_batch_id', // المفتاح الأجنبي في PurchaseItem
            'medicine_id'  // المفتاح في MedicineBatch
        );
    }
}
