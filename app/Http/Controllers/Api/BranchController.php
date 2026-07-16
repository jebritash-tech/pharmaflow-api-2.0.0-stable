<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index() { return response()->json(Branch::all()); }

    public function store(Request $request) {
        $data = $request->validate(['name' => 'required', 'location' => 'required']);
        return response()->json(Branch::create($data), 201);
    }

    public function destroy($id) {
        Branch::destroy($id);
        return response()->json(['message' => 'تم الحذف']);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $data = $request->validate(['name' => 'required', 'location' => 'required']);
        $branch->update($data);
        return response()->json(['message' => 'تم التحديث بنجاح']);
    }
}
