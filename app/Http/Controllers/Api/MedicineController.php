<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
   
    /**
     * عرض قائمة الأدوية المتوفرة مرتبة حسب تاريخ الانتهاء (FEFO)
     */
    public function index(Request $request)
    {
        try {
            // الحصول على معرف الفرع الخاص بالمستخدم المسجل حالياً
            $userBranchId = auth()->user()->branch_id;

            $batches = MedicineBatch::with(['medicine:id,name,barcode,category_id', 'medicine.category:id,name'])
                ->where('branch_id', $userBranchId) // الفلترة بناءً على فرع المستخدم
                ->where('quantity', '>', 0)
                ->orderBy('expiry_date', 'asc')
                ->get()
                ->map(function ($batch) {
                    return [
                        'id'            => $batch->id,
                        'name'          => $batch->medicine->name,
                        'barcode'       => $batch->medicine->barcode,
                        'category'      => $batch->medicine->category->name ?? 'غير مصنف',
                        'selling_price' => (float) $batch->selling_price,
                        'stock'         => (int) $batch->quantity,
                        'expiry_date'   => $batch->expiry_date,
                    ];
                });

            return response()->json($batches, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب قائمة الأدوية الخاصة بفرعك',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // جلب دواء واحد بالباركود
    public function showByBarcode($barcode)
    {
        $medicine = Medicine::with('batches')
            ->where('barcode', $barcode)
            ->firstOrFail();

        return response()->json($medicine);
    }

    // إضافة دواء جديد
    // إضافة دواء جديد
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|unique:medicines|string',
            'category_id' => 'required|exists:categories,id',
        ]);

        return Medicine::create($validated);
        return $medicine->load('category');
    }

    public function update(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            'name' => 'required|string|max:255',
            // Ignore the current record's barcode during update validation
            'barcode' => 'required|string|unique:medicines,barcode,' . $id,
            'category_id' => 'required|exists:categories,id',
        ]);

        $medicine = \App\Models\Medicine::findOrFail($id);
        $medicine->update($request->all());

        return response()->json(['message' => 'تم تحديث الدواء بنجاح']);
    }

    public function destroy($id)
    {
        $medicine = \App\Models\Medicine::findOrFail($id);
        $medicine->delete();

        return response()->json(['message' => 'تم حذف الدواء بنجاح']);
    }
}