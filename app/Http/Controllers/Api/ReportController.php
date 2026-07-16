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
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now());

        $data = Sale::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(total_amount) as total_sales'),
                DB::raw('COUNT(*) as total_orders')
            )->first();

        return response()->json($data);
    }

    // 2. تقرير الأصناف التي قاربت على النفاد (لإعادة الطلب)
    public function lowStockReport()
    {
        return \App\Models\MedicineBatch::with('medicine:id,name')
        ->where('quantity', '>', 0) // فقط الدفعات التي لا تزال تحتوي على كمية
        ->where('quantity', '<', 10) // التي على وشك النفاذ
        ->get()
        ->map(function ($batch) {
            return [
                'id' => $batch->id,
                'name' => $batch->medicine->name,
                'quantity' => $batch->stock_quantity
            ];
        });
    }
    public function overviewStats(Request $request)
    {
        $branchId = $request->query('branch_id');

        return response()->json([
            'daily_sales' => \App\Models\Sale::query()
                ->when($branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', today())->sum('total_amount'),

            'invoice_count' => \App\Models\Sale::query()
                ->when($branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', today())->count(),

            'sales_history' => \App\Models\Sale::query()
                ->when($branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->orderBy('created_at', 'desc')->get(),

            'low_stock_items' => \App\Models\MedicineBatch::with('medicine:id,name')
                ->when($branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->where('quantity', '>', 0)
                ->where('quantity', '<', 10)
                ->get()->map(fn($b) => ['name' => $b->medicine->name, 'stock_quantity' => $b->quantity]),

            'all_batches' => \App\Models\MedicineBatch::with('medicine:id,name')
                ->when($branchId !== 'all', fn($q) => $q->where('branch_id', $branchId))
                ->orderBy('expiry_date', 'asc')->get(),

            // إضافة سجلات المخزون
            'inventory_logs' => \App\Models\InventoryLog::with('medicineBatch.medicine:id,name')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn($log) => [
                    'medicine' => $log->medicineBatch->medicine->name,
                    'type' => $log->type,
                    'quantity' => $log->quantity_changed,
                    'date' => $log->created_at->format('Y-m-d H:i')
                ]),
        ]);
    }

    public function getRecentSales() {
        // جلب آخر 8 مبيعات مع حالة الإرجاع
        $sales = \App\Models\Sale::latest()->take(8)->get()->map(function($sale) {
            return [
                'id' => $sale->id,
                'total_amount' => $sale->total_amount,
                'created_at' => $sale->created_at,
                'is_refunded' => \App\Models\Refund::where('sale_id', $sale->id)->exists()
            ];
        });

        return response()->json(['recent' => $sales]);
    }

    public function getAnalyticsData() {
        // 1. جلب إجمالي المبيعات لآخر 7 أيام
        $dates = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->format('Y-m-d'));
        $totals = $dates->map(fn($date) => Sale::whereDate('created_at', $date)->sum('total_amount'));

        // 2. جلب أكثر الأدوية ربحية
        // الجزء الخاص بأكثر الأدوية ربحية
        $topMedicines = SaleItem::select(
                'medicine_batch_id', 
                // تحديد الجدول هنا هو المفتاح: sale_items.price و sale_items.quantity
                DB::raw('SUM((sale_items.price - medicine_batches.cost_price) * sale_items.quantity) as profit')
            )
            ->join('medicine_batches', 'sale_items.medicine_batch_id', '=', 'medicine_batches.id')
            ->groupBy('medicine_batch_id')
            ->orderBy('profit', 'desc')
            ->take(5)
            ->get();

        // لجلب أسماء الأدوية بجانب الربح (للعرض في الواجهة)
        $topMedicines->each(function ($item) {
            $item->name = $item->batch->medicine->name ?? 'غير معروف';
        });

        return response()->json(['dates' => $dates, 'totals' => $totals, 'top_medicines' => $topMedicines]);
    }
}
