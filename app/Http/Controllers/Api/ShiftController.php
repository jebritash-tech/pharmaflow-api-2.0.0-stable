<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\CashMovement;
use App\Services\ShiftActivityService;

class ShiftController extends Controller
{
    protected ShiftActivityService $activity;
    public function __construct(
        ShiftActivityService $activity
    )
    {
        $this->activity = $activity;
    }
    public function index()
    {
    
        $shifts = Shift::with(
    
            'user:id,name',
    
            'branch:id,name'
    
        )
    
        ->latest()
    
        ->paginate(5);
    
        $shifts->getCollection()->transform(function ($shift) {
    
            $shift->difference =
    
                $shift->closing_cash === null
    
                ? null
    
                : (float)$shift->closing_cash -
    
                  (float)$shift->expected_cash;
    
            return $shift;
    
        });
    
        return $shifts;
    
    }
    public function show(Shift $shift)
    {
        $shift->load([

            'user:id,name',

            'branch:id,name',

            'activities'=>function($q){

                $q->latest();

            }

        ]);

        return response()->json([

            'shift'=>$shift,

            'activities'=>$shift->activities

        ]);
    }
    public function current(Request $request)
    {
        $shift = Shift::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return response()->json([
                'opened' => false
            ]);
        }

        return response()->json([
            'opened' => true,
            'shift' => $shift
        ]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0'
        ]);

        $user = auth()->user();

        $existing = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'لديك وردية مفتوحة بالفعل.'
            ],409);
        }

        $shift = Shift::create([

            'user_id' => $user->id,

            'branch_id' => $user->branch_id,

            'opening_cash' => $request->opening_cash,

            'expected_cash' => $request->opening_cash,

            'cash_sales' => 0,

            'card_sales' => 0,

            'refund_amount' => 0,

            'expenses_amount' => 0,

            'debts_amount' => 0,

            'withdraw_amount' => 0,

            'sales_count' => 0,

            'status' => 'open',

            'opened_at' => now()

        ]);
        $this->activity->log(

            $shift,
        
            ShiftActivityService::OPEN,
        
            'فتح الوردية'
        
        );
        return response()->json($shift);
    }

    public function close(Request $request)
    {
        $request->validate([
            'closing_cash'=>'required|numeric|min:0'
        ]);

        $shift = Shift::where('user_id',$request->user()->id)
            ->where('status','open')
            ->first();

        if(!$shift){

            return response()->json([
                'message'=>'لا توجد وردية مفتوحة.'
            ],404);

        }

        $shift->closing_cash=$request->closing_cash;

        $shift->status='closed';

        $shift->closed_at=now();

        $shift->save();

        return response()->json($shift);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount'=>'required|numeric|min:0.01',
            'reason'=>'required|string|max:255'
        ]);

        $shift = Shift::where('status','open')
            ->where('user_id',auth()->id())
            ->firstOrFail();

        CashMovement::create([

            'shift_id'=>$shift->id,

            'user_id'=>auth()->id(),

            'type'=>'withdraw',

            'amount'=>$request->amount,

            'notes'=>$request->reason

        ]);

        $shift->withdraw_amount += $request->amount;

        $shift->expected_cash -= $request->amount;

        $shift->save();

        return response()->json([
            'message'=>'تم تسجيل السحب'
        ]);
    }
    public function debtPayment(Request $request)
    {
        $request->validate([
            'amount'=>'required|numeric|min:0.01',
            'notes'=>'nullable|string'
        ]);

        $shift = Shift::where('status','open')
            ->where('user_id',auth()->id())
            ->firstOrFail();

        CashMovement::create([

            'shift_id'=>$shift->id,

            'user_id'=>auth()->id(),

            'type'=>'debt_payment',

            'amount'=>$request->amount,

            'notes'=>$request->notes

        ]);

        $shift->debts_amount += $request->amount;

        $shift->expected_cash += $request->amount;

        $shift->save();

        return response()->json([
            'message'=>'تم تسجيل السداد'
        ]);
    }
}