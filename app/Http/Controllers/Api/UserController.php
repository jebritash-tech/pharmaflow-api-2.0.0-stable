<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'salary' => 'nullable|numeric',
            'role' => 'required',
            'branch_id' => 'required|exists:branches,id' // التأكد من اختيار الفرع
        ]);
        $data['password'] = bcrypt($data['password']);
        return User::create($data);
    }
    
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'required',
            'email' => ['required', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'salary' => 'nullable|numeric',
            'role' => 'required',
            'branch_id' => 'required|exists:branches,id'
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'تم تحديث بيانات المستخدم بنجاح',
            'user' => $user->load('branch')
        ]);
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