<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\MedicineBatch;

class InventoryService
{
    /**
     * زيادة المخزون عند الشراء
     */
    public function increase(MedicineBatch $batch): Inventory
    {
        $inventory = Inventory::firstOrCreate(
            [
                'medicine_id' => $batch->medicine_id,
                'branch_id'   => $batch->branch_id,
            ],
            [
                'quantity' => 0
            ]
        );

        $inventory->quantity += $batch->quantity;
        $inventory->save();

        

        return $inventory;
    }

    /**
     * إنقاص المخزون عند البيع
     */
    public function decrease(
        MedicineBatch $batch,
        int $quantity
    ): Inventory {

        $inventory = Inventory::where(
            'medicine_id',
            $batch->medicine_id
        )
        ->where(
            'branch_id',
            $batch->branch_id
        )
        ->firstOrFail();

        $batch->remaining_quantity -= $quantity;
        $batch->save();

        $inventory->quantity -= $quantity;
        $inventory->save();

        

        return $inventory;
    }
}