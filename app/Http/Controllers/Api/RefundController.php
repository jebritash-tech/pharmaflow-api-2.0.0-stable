<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Refund;
use App\Models\MedicineBatch;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'reason' => 'required|string',
            'amount' => 'required|numeric'
        ]);

        $alreadyRefunded = Refund::where('sale_id', $request->sale_id)->exists();
        if ($alreadyRefunded) {
            return response()->json(['message' => 'هذه الفاتورة تم إرجاعها مسبقاً ولا يمكن إرجاعها مرة أخرى.'], 409);
        }
        
        return DB::transaction(function () use ($request) {
            // 1. تسجيل الإرجاع
            $refund = Refund::create([
                'sale_id' => $request->sale_id,
                'amount' => $request->amount,
                'reason' => $request->reason
            ]);

            // 2. استعادة الكميات (عبر جلب عناصر الفاتورة)
            $saleItems = \App\Models\SaleItem::where('sale_id', $request->sale_id)->get();
            foreach ($saleItems as $item) {
                $batch = MedicineBatch::find($item->medicine_batch_id);
                $batch->increment('quantity', $item->quantity);

                // 3. إضافة سجل للمخزون
                InventoryLog::create([
                    'medicine_batch_id' => $batch->id,
                    'type' => 'REFUND',
                    'quantity_changed' => $item->quantity,
                    'notes' => 'Refund for Sale #' . $request->sale_id
                ]);
            }

            return response()->json(['message' => 'تمت عملية الإرجاع بنجاح'], 201);
        });
    }
}
