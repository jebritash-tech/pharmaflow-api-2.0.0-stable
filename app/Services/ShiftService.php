<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Sale;
use App\Services\ShiftActivityService;

class ShiftService
{
    protected ShiftActivityService $activity;
    protected $appends = [
        'icon',
        'color'
    ];

    public function __construct(ShiftActivityService $activity)
    {
        $this->activity = $activity;
    }

    public function getIconAttribute()
    {
        return match($this->type){
            'sale' => '💊',
            'expense' => '💸',
            'withdraw' => '👤',
            'debt' => '📝',       // Icon for new debt
            'debt_payment' => '💵', // Icon for debt payment
            'refund' => '↩️',
            'open' => '🟢',
            'close' => '🔴',
            default => '📌'
        };
    }

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

        // Register sale in shift activity timeline
        $this->activity->log(
            $shift,
            ShiftActivityService::SALE,
            'فاتورة بيع',
            $sale->total_amount,
            'فاتورة رقم: ' . $sale->id,
            ['sale_id' => $sale->id]
        );
    }

    public function registerRefund(float $amount, Shift $shift): void
    {
        $shift->refund_amount += $amount;
        $shift->expected_cash -= $amount;
        $this->recalculateExpectedCash($shift);
        $shift->save();
        
        $this->activity->log(
            $shift,
            ShiftActivityService::REFUND,
            'مرتجع',
            $amount
        );
    }

    public function registerExpense(float $amount, Shift $shift): void
    {
        $shift->expenses_amount += $amount;
        $shift->expected_cash -= $amount;
        $this->recalculateExpectedCash($shift);
        $shift->save();
        
        $this->activity->log(
            $shift,
            ShiftActivityService::EXPENSE,
            'مصروف',
            $amount
        );
    }

    public function registerDebt(float $amount, Shift $shift, string $notes = ''): void
    {
        $shift->debts_amount += $amount;
        // Depending on your workflow, if a debt reduces immediate cash drawer balance or is tracked separately:
        // $shift->expected_cash -= $amount; 
        
        $this->recalculateExpectedCash($shift);
        $shift->save();
        
        $this->activity->log(
            $shift,
            ShiftActivityService::DEBT,
            'تسجيل دين جديد',
            $amount,
            $notes
        );
    }

    public function registerDebtPayment(float $amount, Shift $shift, string $notes = ''): void
    {
        $shift->debts_amount += $amount; // Or track debt collections separately if needed
        $shift->expected_cash += $amount;
        $this->recalculateExpectedCash($shift);
        $shift->save();
        
        $this->activity->log(
            $shift,
            ShiftActivityService::DEBT_PAYMENT,
            'سداد دين',
            $amount,
            $notes
        );
    }

    public function registerWithdrawal(float $amount, Shift $shift): void
    {
        $shift->withdraw_amount += $amount;
        $shift->expected_cash -= $amount;
        $this->recalculateExpectedCash($shift);
        $shift->save();
        
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
            - $shift->withdraw_amount
            - $shift->refund_amount
            - $shift->expenses_amount;

        $shift->save();
    }
}