<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InventoryLog;
use App\Services\ShiftService;
use App\Models\Shift;
class SaleController extends Controller
{
   public function store(Request $request)
    {
        // الحصول على فرع المستخدم الحالي من التوكن (Auth)
        $userBranchId = auth()->user()->branch_id;

        return DB::transaction(function () use ($request, $userBranchId) {
            $totalAmount = 0;
            $totalProfit = 0;

            // 1. فحص شامل للكميات وصلاحية الفرع قبل أي تعديل في قاعدة البيانات
            foreach ($request->items as $item) {
                $batch = MedicineBatch::findOrFail($item['medicine_batch_id']);
                
                // فحص: هل الدواء ينتمي لفرع المستخدم؟
                if ($batch->branch_id !== $userBranchId) {
                    throw new \Exception("خطأ: الصنف {$batch->medicine->name} غير متاح في فرعك الحالي.");
                }

                // فحص: هل الكمية كافية؟
                if ($batch->quantity < $item['quantity']) {
                    throw new \Exception("خطأ: الكمية المتوفرة من {$batch->medicine->name} غير كافية.");
                }
            }
            // الوردية
            $shift = Shift::where('user_id', auth()->id())
                ->where('status','open')
                ->first();

            if(!$shift){

                return response()->json([
                    'message'=>'يجب فتح وردية أولاً.'
                ],422);

            }
            // 2. إنشاء الفاتورة
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'branch_id' => $userBranchId, // استخدام فرع المستخدم لضمان الدقة
                'payment_method' => $request->payment_method,
                'total_amount' => 0,
                'profit_amount' => 0,
                'shift_id' => $shift->id,
            ]);

            

            // 3. خصم الكميات وتسجيل البنود
            foreach ($request->items as $item) {
                $batch = MedicineBatch::findOrFail($item['medicine_batch_id']);
                
                // حساب الربح للبند
                $lineItemProfit = ($batch->selling_price - $batch->cost_price) * $item['quantity'];
                
                $totalAmount += ($batch->selling_price * $item['quantity']);
                $totalProfit += $lineItemProfit;

                // تسجيل البند في الفاتورة
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'medicine_batch_id' => $batch->id,
                    'quantity' => $item['quantity'],
                    'price' => $batch->selling_price,
                    'profit' => $lineItemProfit
                ]);

                // خصم الكمية من المخزون الفعلي
                $batch->decrement('quantity', $item['quantity']);

                // تسجيل حركة المخزون
                \App\Models\InventoryLog::create([
                    'medicine_batch_id' => $batch->id,
                    'type' => 'sale',
                    'quantity_changed' => -($item['quantity']),
                    'notes' => 'Sale ID: ' . $sale->id
                ]);
            }

            // 4. تحديث الإجماليات النهائية
            $sale->update([
                'total_amount' => $totalAmount,
                'profit_amount' => $totalProfit
            ]);

            
            $sale->refresh();

        

            app(ShiftService::class)->registerSale($sale);
            return response()->json(['message' => 'تمت عملية البيع بنجاح', 'sale_id' => $sale->id], 201);
        });
    }

    public function returnMedicine(Request $request, $sale_id)
    {
        return DB::transaction(function () use ($request, $sale_id) {
            $sale = Sale::findOrFail($sale_id);

            foreach ($request->items as $item) {
                // 1. إعادة الكمية للدفعة (Batch)
                $batch = MedicineBatch::findOrFail($item['medicine_batch_id']);
                $batch->increment('quantity', $item['quantity']);

                // 2. تسجيل حركة إرجاع في المخزون
                InventoryLog::create([
                    'medicine_batch_id' => $batch->id,
                    'type' => 'return',
                    'quantity_changed' => $item['quantity'], // بالموجب لأنها دخلت
                    'notes' => 'Returned from Sale ID: ' . $sale_id
                ]);
            }

            return response()->json(['message' => 'تمت عملية الإرجاع بنجاح'], 200);
        });
    }


}