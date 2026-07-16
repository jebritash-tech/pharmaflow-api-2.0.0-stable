<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Sale;
use App\Services\ShiftActivityService;
class ShiftService
{
    protected ShiftActivityService $activity;
    public function __construct(
        ShiftActivityService $activity
    )
    {
        $this->activity = $activity;
    }
    /**
     * تحديث بيانات الوردية بعد عملية بيع
     */
    public function registerSale(Sale $sale): void
    {
        $shift = Shift::find($sale->shift_id);

        if (!$shift) {
            return;
        }

        $shift->sales_count++;

        if ($sale->payment_method === 'cash') {

            $shift->cash_sales += $sale->total_amount;

        } else {

            $shift->card_sales += $sale->total_amount;

        }

        $this->recalculateExpectedCash($shift);

        $shift->save();
        // Shift Activity Log
        $this->activity->log(

            $shift,
        
            ShiftActivityService::SALE,
        
            'فاتورة بيع',
        
            $sale->total_amount,
        
            $sale
        
        );
    }

    public function registerRefund(
        float $amount,
        Shift $shift
    ): void
    {

        $shift->refund_amount += $amount;

        $shift->expected_cash -= $amount;
        
        $shift->save();
        $this->recalculateExpectedCash($shift);
        $this->activity->log(

            $shift,
        
            ShiftActivityService::REFUND,
        
            'مرتجع',
        
            $amount
        
        );

    }

    public function registerExpense(
        float $amount,
        Shift $shift
    ): void
    {

        $shift->expenses_amount += $amount;

        $shift->expected_cash -= $amount;
        
        $shift->save();
        $this->recalculateExpectedCash($shift);
        $this->activity->log(

            $shift,
        
            ShiftActivityService::EXPENSE,
        
            'مصروف',
        
            $amount
        
        );

    }

    public function registerDebt(
        float $amount,
        Shift $shift
    ): void
    {

        $shift->debts_amount += $amount;

        $shift->expected_cash -= $amount;
        
        $shift->save();
        $this->recalculateExpectedCash($shift);
        $this->activity->log(

            $shift,
        
            ShiftActivityService::DEBT_PAYMENT,
        
            'سداد دين',
        
            $amount
        
        );
    }

    public function registerWithdrawal(
        float $amount,
        Shift $shift
    ): void
    {

        $shift->withdraw_amount += $amount;

        $shift->expected_cash -= $amount;

        $shift->save();
        $this->recalculateExpectedCash($shift);
        $this->activity->log(

            $shift,
        
            ShiftActivityService::WITHDRAW,
        
            'سحب موظف',
        
            $amount
        
        );

    }

    public function recalculateExpectedCash(Shift $shift): void
    {
        $shift->expected_cash =
            $shift->opening_cash
            + $shift->cash_sales
            + $shift->debts_amount
            - $shift->withdraw_amount
            - $shift->refund_amount
            - $shift->expenses_amount;

        $shift->save();
    }
}