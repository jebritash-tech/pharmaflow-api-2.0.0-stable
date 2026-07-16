<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function index() {
        return User::with('branch')->get();
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required|min:6',
            'role' => 'required',
            'branch_id' => 'required|exists:branches,id' // التأكد من اختيار الفرع
        ]);
        $data['password'] = bcrypt($data['password']);
        return User::create($data);
    }
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // الحذف الناعم: المستخدم سيختفي من النظام ولكن البيانات تبقى في القاعدة
        $user->delete(); 
        
        return response()->json(['message' => 'تم نقل المستخدم إلى الأرشيف بنجاح']);
    }

    public function currentUser() {
        return auth()->user()->load('branch'); // جلب المستخدم مع بيانات فرعه
    }
}
