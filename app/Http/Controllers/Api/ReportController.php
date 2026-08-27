<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\MedicineBatch;
use App\Models\Refund;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // 1. إجمالي المبيعات والأرباح خلال فترة
    public function salesReport(Request $request)
    {
        // Find the currently authenticated user's active open shift
        $activeShift = Shift::where('user_id', auth()->id())
                                ->where('status', 'open')
                                ->first();

            if (!$activeShift) {
                return response()->json([
                    'message' => 'No active shift found for the current user.',
                    'data' => []
                ], 403);
            }

            // Query sales restricted exclusively to the active shift ID
            $sales = Sale::where('shift_id', $activeShift->id)
                        ->with('items.batch', 'items.unit')
                        ->latest()
                        ->paginate(20);

            return response()->json($sales);
    }

    // 2. تقرير الأصناف التي قاربت على النفاد (لإعادة الطلب)
    public function lowStockReport(Request $request)
    {
        $branchId = $request->query('branch_id');

        return MedicineBatch::with('medicine:id,name')
            ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
            ->where('remaining_quantity', '>', 0) // فقط الدفعات التي لا تزال تحتوي على كمية
            ->where('remaining_quantity', '<', 1000) // التي على وشك النفاذ
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'name' => $batch->medicine->name,
                    'quantity' => $batch->remaining_quantity
                ];
            });
    }

public function overviewStats(Request $request)
    {
        $branchId = $request->query('branch_id');

        return response()->json([
            'daily_sales' => Sale::query()
                ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', today())->sum('total_amount'),

            'invoice_count' => Sale::query()
                ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', today())->count(),

            'sales_history' => Sale::query()
                ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->orderBy('created_at', 'desc')->get(),

            'low_stock_items' => MedicineBatch::with(['medicine:id,name'])
                ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->where('remaining_quantity', '>', 0)
                ->where('remaining_quantity', '<', 1000)
                ->get()
                ->map(function ($batch) {
                    // Fetch the pivot record using its primary key (purchase_unit_id)
                    $unitRecord = DB::table('medicine_units')
                        ->where('id', $batch->purchase_unit_id)
                        ->select('factor', 'unit_id')
                        ->first();

                    $globalUnit = $unitRecord ? DB::table('units')->where('id', $unitRecord->unit_id)->first() : null;
                    $unitName = $globalUnit->name ?? 'قطعة';
                    
                    $factor = $unitRecord && $unitRecord->factor > 0 ? (float) $unitRecord->factor : 1;
                    $remainingQty = (float) $batch->remaining_quantity;
                    $convertedQty = $factor > 0 ? $remainingQty / $factor : $remainingQty;

                    return [
                        'id' => $batch->id,
                        'name' => $batch->medicine->name ?? 'غير معروف',
                        'stock_quantity' => number_format($convertedQty, 0) . ' ' . $unitName,
                        'unit_name' => $unitName
                    ];
                }),

           'all_batches' => MedicineBatch::with(['medicine:id,name'])
                ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->orderBy('expiry_date', 'asc')
                ->get()
                ->map(function ($batch) {
                    // Fetch the pivot record using its primary key (purchase_unit_id)
                    $unitRecord = DB::table('medicine_units')
                        ->where('id', $batch->purchase_unit_id)
                        ->select('factor', 'unit_id')
                        ->first();

                    $globalUnit = $unitRecord ? DB::table('units')->where('id', $unitRecord->unit_id)->first() : null;
                    $unitName = $globalUnit->name ?? 'قطعة';
                    
                    $factor = $unitRecord && $unitRecord->factor > 0 ? (float) $unitRecord->factor : 1;
                    $remainingQty = (float) $batch->remaining_quantity;

                    // Calculate converted quantity by dividing base quantity by the factor
                    $convertedQty = $factor > 0 ? $remainingQty / $factor : $remainingQty;

                    return [
                        'id' => $batch->id,
                        'medicine_id' => $batch->medicine_id,
                        'batch_number' => $batch->batch_number,
                        'remaining_quantity' => $remainingQty,
                        'formatted_stock' => number_format($convertedQty, 0) . ' ' . $unitName,
                        'converted_quantity' => number_format($convertedQty, 0) . ' ' . $unitName,
                        'expiry_date' => $batch->expiry_date,
                        'medicine' => [
                            'id' => $batch->medicine->id ?? null,
                            'name' => $batch->medicine->name ?? 'غير معروف'
                        ],
                        'unit' => [
                            'id' => $unitRecord->unit_id ?? $batch->purchase_unit_id,
                            'name' => $unitName
                        ]
                    ];
                }),
        
            'inventory_movements' => \App\Models\InventoryMovement::with(['medicine:id,name'])
                ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn($movement) => [
                    'medicine' => $movement->medicine->name ?? 'غير معروف',
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'date' => $movement->created_at?->format('Y-m-d H:i')
                ]),
        ]);
    }

    public function getRecentSales(Request $request) 
    {
        $branchId = $request->query('branch_id');

        // جلب آخر 8 مبيعات مع حالة الإرجاع
        $sales = Sale::query()
            ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->take(8)
            ->get()
            ->map(function($sale) {
                return [
                    'id' => $sale->id,
                    'total_amount' => $sale->total_amount,
                    'created_at' => $sale->created_at,
                    'is_refunded' => Refund::where('sale_id', $sale->id)->exists()
                ];
            });

        return response()->json(['recent' => $sales]);
    }

    public function getAnalyticsData(Request $request) 
    {
        $branchId = $request->query('branch_id');

        // 1. جلب إجمالي المبيعات لآخر 7 أيام
        $dates = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));
        $totals = $dates->map(function($date) use ($branchId) {
            return Sale::query()
                ->when($branchId && $branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', $date)
                ->sum('total_amount');
        });

        // 2. جلب أكثر الأدوية ربحية
        $topMedicines = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('medicine_batches', 'sale_items.medicine_batch_id', '=', 'medicine_batches.id')
            ->join('medicines', 'medicine_batches.medicine_id', '=', 'medicines.id')
            ->when($branchId && $branchId !== 'all', fn($q) => $q->where('sales.branch_id', $branchId))
            ->select(
                'medicines.name as name',
                DB::raw('SUM((sale_items.price - medicine_batches.cost_price) * sale_items.quantity) as profit')
            )
            ->groupBy('medicines.id', 'medicines.name')
            ->orderBy('profit', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'dates' => $dates, 
            'totals' => $totals, 
            'top_medicines' => $topMedicines
        ]);
    }
}