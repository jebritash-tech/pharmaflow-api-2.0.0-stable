<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index() { return response()->json(Category::all()); }

    public function store(Request $request) {
        $data = $request->validate(['name' => 'required']);
        return response()->json(Category::create($data), 201);
    }

    public function update(Request $request, $id) {
        $category = Category::findOrFail($id);
        $data = $request->validate(['name' => 'required|unique:categories,name,'.$id]);
        $category->update($data);
        return response()->json(['message' => 'تم التحديث بنجاح']);
    }

    public function destroy($id) {
        Category::destroy($id);
        return response()->json(['message' => 'تم الحذف']);
    }
}
