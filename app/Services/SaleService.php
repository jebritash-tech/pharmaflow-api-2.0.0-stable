<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePrice;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class SaleService
{
   public function loadMedicines(int $branchId)
    {
        return Medicine::query()
            ->with([
                'units.unit', 
                'inventory' => function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                },
                // Scoped strictly to the user's branch batches
                'batches' => function ($q) use ($branchId) {
                    $q->where('remaining_quantity', '>', 0)
                      ->where('branch_id', $branchId) // <--- Fixed: restricted to user branch
                      ->orderBy('expiry_date', 'asc'); // FIFO batch priority
                },
                'batches.prices.unit',
            ])
            ->whereHas('batches', function ($q) use ($branchId) {
                $q->where('remaining_quantity', '>', 0)
                  ->where('branch_id', $branchId); // <--- Fixed: restricted to user branch
            })
            ->get();
    }
    /*
    |--------------------------------------------------------------------------
    | FEFO
    |--------------------------------------------------------------------------
    */

   public function getBatch(
    int $medicineId,
    int $requiredBaseQuantity,
    int $branchId
    )
    {
        return MedicineBatch::query()
            ->where('medicine_id', $medicineId)
            ->where('branch_id', $branchId) 
            ->where('remaining_quantity', '>=', $requiredBaseQuantity)
            ->orderBy('expiry_date')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Price
    |--------------------------------------------------------------------------
    */

    public function getPrice(
        int $batchId,
        int $medicineUnitId
    )
    {
        return MedicinePrice::query()
            ->where('batch_id', $batchId)
            ->where('unit_id', $medicineUnitId)
            ->where('is_active', true)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Create Sale
    |--------------------------------------------------------------------------
    */

    public function create(array $data)
    {
        \Log::info('POS Sale Request Payload:', $data); 
        return DB::transaction(function () use ($data) {

            $sale = Sale::create([
                'branch_id' => $data['branch_id'],
                'user_id' => $data['user_id'],
                'shift_id' => $data['shift_id'],
                'total_amount' => $data['total_amount'],
                'profit_amount' => 0,
                'payment_method' => $data['payment_method'],
                'bank_name' => $data['bank_name'] ?? null,
                'bank_reference' => $data['bank_reference'] ?? null,
                'bank_transfer_date' => $data['bank_transfer_date'] ?? null,
                'bank_notes' => $data['bank_notes'] ?? null,
            ]);

            $totalProfit = 0;

            // Fixed: use $data['items'] instead of undefined $request->items
            foreach ($data['items'] as $itemData) {
                $batch = MedicineBatch::findOrFail($itemData['medicine_batch_id']);
                
                // Find the conversion factor for the selected unit from the medicine's units relation
                $medicineUnit = \App\Models\MedicineUnit::where('medicine_id', $batch->medicine_id)
                    ->where('unit_id', $itemData['medicine_unit_id'])
                    ->first();
                    
                $factor = $medicineUnit ? $medicineUnit->factor : 1;
                
                // Calculate total base units to deduct
                $quantityBase = $itemData['quantity'] * $factor;

                // Validate stock sufficiency using the calculated base quantity
                if ($batch->remaining_quantity < $quantityBase) {
                    throw new \Exception('الكمية غير متوفرة في المخزون');
                }

                // Decrement the batch correctly by the base amount
                $batch->decrement('remaining_quantity', $quantityBase);
                
                // Save the correct quantity_base in your sale item record
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'medicine_batch_id' => $batch->id,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'medicine_unit_id' => $itemData['medicine_unit_id'],
                    'quantity_base' => $quantityBase,
                    'selling_price' => $itemData['selling_price'] ?? 0,
                ]);
            }
            
            $sale->update([
                'profit_amount' => $totalProfit
            ]);

            return $sale->fresh();
        });
    }
}