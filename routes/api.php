<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\Api\{
    CategoryController,
    MedicineController,
    SupplierController,
    PurchaseController,
    SaleController,
    InventoryLogController,
    BranchController,
    AdminController,
    UserController,
    AuthController,
    ReportController,
    RefundController,
    PasswordResetController,
    AnalyticsController,
    ShiftController,
    ExpenseController
};


// تسجيل الدخول
Route::post('/login', [AuthController::class, 'login']);
// Send email with reset token
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
// Reset password
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Santcum
Route::middleware('auth:sanctum')->group(function () {
    // Admin Debts and Shifts
    Route::get('/shifts', [ShiftController::class,'index']);

    Route::get('/debts', [DebtController::class,'index']);
    Route::get('/shifts/{shift}',[ShiftController::class,'show']);
    // ديون الموظفين
    Route::post(
    '/employee-finance/withdraw',
    
    );
    Route::post('/shift/withdraw', [ShiftController::class, 'withdraw']);
    Route::post('/shift/debt-payment', [ShiftController::class, 'debtPayment']);

    // المصروفات
    Route::post('/expenses',[ExpenseController::class,'store']);
    // الورديات
    Route::get('/shift/current',[ShiftController::class,'current']);

    Route::post('/shift/open',[ShiftController::class,'open']);

    Route::post('/shift/close',[ShiftController::class,'close']);

    // المستخدم الحالي
    Route::get('/current-user', [AuthController::class, 'me']);

    // الأدارة
    // 2. مسارات المدير (Admin API)
    Route::prefix('admin')->group(function () {
        Route::middleware(EnsureUserIsAdmin::class)->group(function () {
            // إحصائيات لوحة التحكم
            Route::get('/stats', [AdminController::class, 'getStats']);
            
            // كتالوج الأدوية (للمدير فقط)
            Route::get('/medicine-catalogue', [AdminController::class, 'getMedicineCatalogue']);
            Route::post('/medicine-catalogue', [AdminController::class, 'storeMedicine']);
            Route::post('/medicines', [AdminController::class, 'storeMedicine']); // إضافة دواء
            
            // إدارة المشتريات
            Route::get('/purchases', [AdminController::class, 'indexPurchases']);
            Route::post('/purchases', [AdminController::class, 'storePurchase']);
            Route::get('/analytics', [AdminController::class, 'getAnalytics']);
            // بيانات الرسوم البيانية (Analytics)
            Route::get('/analytics-data', [ReportController::class, 'getAnalyticsData']);
            Route::get('/analytics/dashboard', [AnalyticsController::class, 'getDashboardData']);
        });
    });

    // الفروع
    Route::apiResource('branches', App\Http\Controllers\Api\BranchController::class);

    // التقارير
    Route::get('reports/sales', [\App\Http\Controllers\Api\ReportController::class, 'getRecentSales']);
    Route::get('reports/low-stock', [\App\Http\Controllers\Api\ReportController::class, 'lowStockReport']);
    Route::get('/admin/overview-stats', [ReportController::class, 'overviewStats']);
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    // المرتجعات
    Route::post('returns', [\App\Http\Controllers\Api\ReturnController::class, 'store']);
    Route::post('/refunds', [RefundController::class,'store']);

    // سجل حركات دواء معين
    Route::get('logs/medicine/{medicineId}', [InventoryLogController::class, 'getMedicineLogs']);

    // جميع السجلات
    Route::get('logs', [InventoryLogController::class, 'index']);

    // 1. إدارة التصنيفات
    Route::apiResource('categories', CategoryController::class);

    // 2. إدارة الأدوية (الكتالوج)
    Route::apiResource('medicines', MedicineController::class);

    // 3. إدارة الموردين
    Route::apiResource('suppliers', SupplierController::class);

    // 4. المشتريات (إدخال المخزون)
    Route::post('purchases', [PurchaseController::class, 'store']);

    // 5. المبيعات (نقطة البيع - POS)
    Route::post('sales', [SaleController::class, 'store']);

    // المستخدمين
    Route::apiResource('users', UserController::class);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/create-admin-dev', function () {
    $admin = User::create([
        'name' => 'Admin User',
        'email' => 'jebritash@gmail.com',
        'password' => Hash::make('12345678'), // Change this password
        'role' => 'admin',
        'branch_id' => null, // Set a valid branch ID if your DB requires it
    ]);

    return response()->json(['message' => 'Admin created successfully', 'user' => $admin]);
});