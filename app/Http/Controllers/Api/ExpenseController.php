<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ExpenseService;
use App\Models\Shift;

class ExpenseController extends Controller
{
    public function store(
        Request $request,
        ExpenseService $service
    ){

        $request->validate([

            'title'=>'required',

            'amount'=>'required|numeric|min:1',

            'notes'=>'nullable'

        ]);

        $shift = Shift::where(

            'user_id',

            $request->user()->id

        )->where(

            'status',

            'open'

        )->firstOrFail();

        return $service->create([

            'shift_id'=>$shift->id,

            'user_id'=>$request->user()->id,

            'title'=>$request->title,

            'amount'=>$request->amount,

            'notes'=>$request->notes

        ]);

    }
}