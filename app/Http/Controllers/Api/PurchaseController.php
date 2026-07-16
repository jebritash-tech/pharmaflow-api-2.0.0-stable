<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'items' => 'required|array',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.batch_number' => 'required|string',
            'items.*.expiry_date' => 'required|date',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric',
            'items.*.selling_price' => 'required|numeric',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. حساب الإجمالي
            $totalAmount = collect($request->items)->sum(function ($item) {
                return $item['cost_price'] * $item['quantity'];
            });

            // 2. إنشاء الفاتورة
            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'total_amount' => $totalAmount,
            ]);

            // 3. إضافة العناصر وإنشاء الدفعات (Batches)
            foreach ($request->items as $item) {
                // إنشاء الدفعة في جدول الدفعات
                $batch = MedicineBatch::create([
                    'medicine_id' => $item['medicine_id'],
                    'batch_number' => $item['batch_number'],
                    'expiry_date' => $item['expiry_date'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'selling_price' => $item['selling_price'],
                ]);

                // تسجيل تفاصيل العنصر في الفاتورة
                $purchase->items()->create([
                    'medicine_batch_id' => $batch->id,
                    'quantity' => $item['quantity'],
                    'price' => $item['cost_price'],
                ]);
                
                // إضافة سجل في الـ InventoryLog
                \App\Models\InventoryLog::create([
                    'medicine_batch_id' => $batch->id,
                    'type' => 'purchase',
                    'quantity_changed' => $item['quantity'], // موجب لأنها إضافة
                    'notes' => 'Purchase Invoice ID: ' . $purchase->id
                ]);
            }

            return response()->json(['message' => 'تم تسجيل المشتريات بنجاح', 'purchase_id' => $purchase->id], 201);
        });
    }
}