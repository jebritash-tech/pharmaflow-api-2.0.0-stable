<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index() { return response()->json(Supplier::all()); }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required',
            'phone' => 'nullable',
            'email' => 'nullable|email'
        ]);
        return response()->json(Supplier::create($data), 201);
    }

    public function update(Request $request, $id) {
        $supplier = Supplier::findOrFail($id);
        $data = $request->validate(['name' => 'required', 'phone' => 'nullable', 'email' => 'nullable']);
        $supplier->update($data);
        return response()->json(['message' => 'تم التحديث بنجاح']);
    }

    public function destroy($id) {
        Supplier::destroy($id);
        return response()->json(['message' => 'تم الحذف']);
    }
}
