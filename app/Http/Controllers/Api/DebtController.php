<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    //
    public function index()
    {
        return Debt::with(
            'user:id,name',
            'branch:id,name','sale','payments'
        )
        ->latest()
        ->paginate(5);
    }

    public function store(Request $request)
    {
       $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'total_amount' => 'required|numeric|min:1',
            'due_date' => 'nullable|date'
        ]);

        $debt = Debt::create([
            'user_id' => $request->user_id ?? auth()->id(),
            'branch_id' => $request->branch_id,
            'total_amount' => $request->total_amount,
            'paid_amount' => 0,
            'remaining_amount' => $request->total_amount,
            'status' => 'pending',
            'due_date' => $request->due_date
        ]);

        return response()->json([
            'message' => 'تم تسجيل الدين بنجاح',
            'data' => $debt->load('branch'),
           
        ], 201);
    }
    public function show($id)
    {
        return Debt::with('user:id,name', 'branch:id,name', 'payments')->findOrFail($id);
    }
    public function payment(
        Request $request,
        Debt $debt
    ){
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);
        
        DB::transaction(function() use ($request, $debt) {
            DebtPayment::create([
                'debt_id' => $debt->id,
                'user_id' => auth()->id(),
                'amount' => $request->amount
            ]);
        
            $debt->paid_amount += $request->amount;
            $debt->remaining_amount -= $request->amount;
        
            if ($debt->remaining_amount <= 0) {
                $debt->status = 'paid';
            } else {
                $debt->status = 'partial';
            }
        
            $debt->save();
        });
        
        return response()->json([
            'message' => 'تم السداد'
        ]);
    }
}
