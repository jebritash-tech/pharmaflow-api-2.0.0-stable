<?php

namespace App\Models;

use App\Models\Medicine;
use App\Models\SaleItem;
use App\Models\MedicinePrice;
use Illuminate\Database\Eloquent\Model;

class MedicineBatch extends Model
{

    protected $fillable = [
        'medicine_id', 
        'purchase_item_id',
        'branch_id' ,
        'batch_number', 
        'expiry_date', 
        'buy_price', 
        'selling_price',
        'quantity',
        'remaining_quantity',
        'purchase_unit_id',
        
        
    ];

    protected $casts=[

        'quantity_base'=>'integer'
    
    ];
    //
    public function medicine() {
        return $this->belongsTo(Medicine::class);
    }

    public function saleItems() {
        return $this->hasMany(SaleItem::class);
    }

    public function prices()
    {
        return $this->hasMany(

            MedicinePrice::class,

            'batch_id'

        );
    }

    public function purchaseItem()
    {
        return $this->belongsTo(
            PurchaseItem::class
        );
    }
   public function unit()
    {
        return $this->belongsTo(\App\Models\Unit::class, 'purchase_unit_id');
    }
    
   // Relate to the medicine_units pivot model/table properly
    public function purchaseUnit()
    {
        return $this->belongsTo(MedicineUnit::class, 'purchase_unit_id');
    }

    // Accessor to calculate and format based on the package unit factor
    public function getConvertedQuantityAttribute()
    {
        // Get the pivot record using purchase_unit_id
        $medicineUnit = \Illuminate\Support\Facades\DB::table('medicine_units')
            ->where('id', $this->purchase_unit_id)
            ->first();

        if (!$medicineUnit) {
            return number_format($this->remaining_quantity, 0);
        }

        // Get the actual global unit name (e.g., Box)
        $unit = \App\Models\Unit::find($medicineUnit->unit_id);
        $unitName = $unit ? $unit->name : '';

        $factor = $medicineUnit->factor ?? 1;

        // If factor is greater than 1, calculate how many boxes/packs vs base pieces
        if ($factor > 1) {
            $packs = floor($this->remaining_quantity / $factor);
            $remainder = $this->remaining_quantity % $factor;

            if ($remainder == 0) {
                return number_format($packs, 0) . ' ' . $unitName;
            }
            return number_format($packs, 0) . ' ' . $unitName . ' (' . number_format($this->remaining_quantity, 0) . ' Pieces)';
        }

        return number_format($this->remaining_quantity, 0) . ' ' . ($unitName ?: 'Piece');
    }

    public function getFormattedStockAttribute()
    {
        return $this->getConvertedQuantityAttribute();
    }
}
