<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ExpenseService;
use App\Models\Shift;
use App\Models\Expense;
class ExpenseController extends Controller
{
public function store(Request $request, ExpenseService $service)
{
    $request->validate([
        'title'    => 'required|string|max:255',
        'amount'   => 'required|numeric|min:1',
        'shift_id' => 'nullable|exists:shifts,id',
        'notes'    => 'nullable|string'
    ]);

    // Check if a shift_id was explicitly passed, otherwise look for an open shift
    $shiftId = $request->input('shift_id');

    if (!$shiftId) {
        $activeShift = Shift::where('user_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        // If no open shift exists, fall back to the most recent shift or create a safe fallback
        // so it never crashes with a ModelNotFound / Builder exception.
        $shiftId = $activeShift ? $activeShift->id : Shift::latest()->value('id');
    }

    if (!$shiftId) {
        return response()->json([
            'message' => 'لا توجد أي وردية مسجلة في النظام لتسجيل المصروف تحتها.'
        ], 422);
    }

    return $service->create([
        'shift_id' => $shiftId,
        'user_id'  => $request->user()->id,
        'title'    => $request->title,
        'amount'   => $request->amount,
        'notes'    => $request->notes
    ]);
}

    public function index(Request $request)
    {
        $expenses = Expense::with(['user:id,name', 'shift:id'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($expenses);
    }

    public function show($id)
    {
        $expense = Expense::with(['user:id,name', 'shift:id'])->findOrFail($id);

        return response()->json($expense);
    }
}