<?php

namespace App\Services;

use App\Models\Shift;
use App\Repositories\ExpenseRepository;

class ExpenseService
{
    public function __construct(
        protected ExpenseRepository $expenses,
        protected ShiftService $shiftService
    ){}

    public function create(array $data)
    {
        $expense = $this->expenses->create($data);

        $shift = Shift::find(
            $data['shift_id']
        );

        $this->shiftService
            ->registerExpense(
                $expense->amount,
                $shift
            );

        return $expense;
    }
}