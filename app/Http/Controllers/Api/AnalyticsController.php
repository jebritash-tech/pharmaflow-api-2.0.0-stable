<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        $today = Carbon::today();

        return response()->json([
            'kpis'               => $this->kpis(),
            'sales_profit_chart' => $this->salesProfitChart(),
            'growth_chart'       => $this->growthChart(),
            'inventory'          => $this->inventoryMetrics(),
            'top_profit_products'=> $this->topProfitProducts(),
            'top_selling'        => $this->topSellingProducts(),
            'suppliers'          => $this->supplierAnalytics(),
            'forecast'           => $this->forecast(),
            'peak_hours'         => $this->peakHours(),
            'recent_sales'       => $this->recentSales(),
        ]);
    }

    private function kpis()
    {
        return [
            'today_sales' => DB::table('sales')
                ->whereDate('created_at', today())
                ->sum('total_amount'),

            'today_profit' => DB::table('sales')
                ->whereDate('created_at', today())
                ->sum('profit_amount'),

            'today_invoices' => DB::table('sales')
                ->whereDate('created_at', today())
                ->count(),

            'avg_invoice' => DB::table('sales')
                ->whereDate('created_at', today())
                ->avg('total_amount'),

            'inventory_value' => DB::table('medicine_batches')
                ->selectRaw('SUM(quantity * cost_price) as total')
                ->value('total'),

            'frozen_capital' => $this->frozenCapital(),
        ];
    }

    private function salesProfitChart()
    {
        return DB::table('sales')
            ->selectRaw('EXTRACT(MONTH FROM created_at) as month')
            ->selectRaw('SUM(total_amount) as sales')
            ->selectRaw('SUM(profit_amount) as profit')
            ->whereYear('created_at', now()->year)
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderByRaw('EXTRACT(MONTH FROM created_at)')
            ->get()
            ->map(function ($row) {

                $row->month = Carbon::create()
                    ->month((int) $row->month)
                    ->locale('ar')
                    ->translatedFormat('F');

                return $row;
            });
    }

    private function growthChart()
    {
        $months = DB::table('sales')
            ->selectRaw('EXTRACT(MONTH FROM created_at) as month')
            ->selectRaw('SUM(total_amount) as sales')
            ->whereYear('created_at', now()->year)
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->orderByRaw('EXTRACT(MONTH FROM created_at)')
            ->get();
    
        $result = [];
    
        foreach ($months as $index => $month)
        {
            if ($index == 0) {
                $growth = 0;
            } else {
    
                $previous = $months[$index - 1]->sales;
    
                $growth = $previous > 0
                    ? (($month->sales - $previous) / $previous) * 100
                    : 0;
            }
    
            $result[] = [
                'month' => Carbon::create()
                    ->month((int) $month->month)
                    ->locale('ar')
                    ->translatedFormat('F'),
    
                'growth' => round($growth, 2)
            ];
        }
    
        return $result;
    }

    private function inventoryMetrics()
    {
        return [
            'inventory_value' => DB::table('medicine_batches')
                ->selectRaw('SUM(quantity * cost_price) total')
                ->value('total'),

            'low_stock' => DB::table('medicine_batches')
                ->whereColumn('quantity', '<=', 'min_stock')
                ->count(),

            'expiring_soon' => DB::table('medicine_batches')
                ->whereDate(
                    'expiry_date',
                    '<=',
                    now()->addDays(60)
                )
                ->count(),

            'health_score' => $this->inventoryHealthScore()
        ];
    }

    private function inventoryHealthScore()
    {
        $lowStock = DB::table('medicine_batches')
            ->whereColumn('quantity','<=','min_stock')
            ->count();

        $expiring = DB::table('medicine_batches')
            ->whereDate('expiry_date','<=',now()->addDays(60))
            ->count();

        $score = 100 - ($lowStock * 2) - ($expiring * 2);

        return max(0, $score);
    }

    private function topSellingProducts()
    {
        return DB::table('sale_items')
            ->join(
                'medicine_batches',
                'sale_items.medicine_batch_id',
                '=',
                'medicine_batches.id'
            )
            ->join(
                'medicines',
                'medicine_batches.medicine_id',
                '=',
                'medicines.id'
            )
            ->select(
                'medicines.name',
                DB::raw('SUM(sale_items.quantity) qty')
            )
            ->groupBy('medicines.id','medicines.name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();
    }

    private function topProfitProducts()
    {
        return DB::table('sale_items')
            ->join(
                'medicine_batches',
                'sale_items.medicine_batch_id',
                '=',
                'medicine_batches.id'
            )
            ->join(
                'medicines',
                'medicine_batches.medicine_id',
                '=',
                'medicines.id'
            )
            ->select(
                'medicines.name',
                DB::raw('SUM(sale_items.profit) profit')
            )
            ->groupBy('medicines.id','medicines.name')
            ->orderByDesc('profit')
            ->limit(10)
            ->get();
    }

    private function supplierAnalytics()
    {
        return DB::table('purchase_items')
            ->join('purchases','purchase_items.purchase_id','=','purchases.id')
            ->join('suppliers','purchases.supplier_id','=','suppliers.id')
            ->select(
                'suppliers.name',
                DB::raw('SUM(purchase_items.quantity * purchase_items.price) total')
            )
            ->groupBy('suppliers.id','suppliers.name')
            ->orderByDesc('total')
            ->get();
    }

    private function peakHours()
    {
        return DB::table('sales')
            ->selectRaw('EXTRACT(HOUR FROM created_at) as hour')
            ->selectRaw('COUNT(*) as invoices')
            ->groupByRaw('EXTRACT(HOUR FROM created_at)')
            ->orderByRaw('EXTRACT(HOUR FROM created_at)')
            ->get()
            ->map(function ($row) {
    
                $row->hour = Carbon::createFromTime(
                    (int) $row->hour,
                    0
                )->format('H:i');
    
                return $row;
            });
    }

    private function recentSales()
    {
        return DB::table('sales')
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'total_amount',
                'profit_amount',
                'created_at'
            ]);
    }

    private function frozenCapital()
    {
        return DB::table('medicine_batches')
            ->leftJoin(
                'sale_items',
                'medicine_batches.id',
                '=',
                'sale_items.medicine_batch_id'
            )
            ->where(function($q){
                $q->whereNull('sale_items.created_at')
                  ->orWhere(
                      'sale_items.created_at',
                      '<',
                      now()->subDays(90)
                  );
            })
            ->selectRaw(
                'SUM(medicine_batches.quantity * medicine_batches.cost_price) total'
            )
            ->value('total') ?? 0;
    }

    private function forecast()
    {
        return DB::table('sale_items')
            ->join(
                'medicine_batches',
                'sale_items.medicine_batch_id',
                '=',
                'medicine_batches.id'
            )
            ->join(
                'medicines',
                'medicine_batches.medicine_id',
                '=',
                'medicines.id'
            )
            ->select(
                'medicines.name',
                DB::raw('AVG(sale_items.quantity) avg_daily_sales'),
                DB::raw('SUM(medicine_batches.quantity) current_stock')
            )
            ->groupBy('medicines.id','medicines.name')
            ->limit(20)
            ->get();
    }
}