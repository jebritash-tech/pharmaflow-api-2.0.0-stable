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
    ExpenseController,
    DebtController,
    UnitController,
    MedicineUnitController,
    MedicineBatchesController,
    PriceEngineRuleController,
    PriceEngineController,
    InventoryController,
    SalaryController
};


// تسجيل الدخول
Route::post('/login', [AuthController::class, 'login']);
// Send email with reset token
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
// Reset password
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

// Santcum
//Route::middleware('auth:sanctum')->group(function () {
    // Stocktaking
    Route::get('/inventories', [InventoryController::class, 'index']);
    Route::post('/inventories/adjust', [InventoryController::class, 'adjust']); 
    // Pricing Engine
            
    Route::post(

        'price-engine/regenerate-all',

        [PriceEngineController::class,'regenerateAll']

    );
    Route::patch(
        '/medicines/{medicine}/pricing-rule',
        [MedicineController::class, 'updatePricingRule']
    );
    Route::prefix('price-engine')->group(function () {
        Route::post(
            '/regenerate-current',
            [PriceEngineController::class, 'regenerateCurrent']
        );
        Route::get(
            '/rules',
                [PriceEngineController::class, 'rules']
        );
        Route::get(
            'rules',
            [PriceEngineController::class, 'index']
        );

        Route::post(
            'rules',
            [PriceEngineController::class, 'store']
        );

        Route::put(
            'rules/{rule}',
            [PriceEngineController::class, 'update']
        );

        Route::delete(
            'rules/{rule}',
            [PriceEngineController::class, 'destroy']
        );

        Route::patch(
            'rules/{rule}/toggle',
            [PriceEngineController::class, 'toggle']
        );

        Route::post(
            'simulate',
            [PriceEngineController::class, 'simulate']
        );

    });
    
    Route::post(

            'batches/{batch}/regenerate-prices',

            [PriceEngineController::class, 'regenerate']

    );
    
   
    Route::prefix('salaries')->group(function () {

        Route::get(
            '/',
            [SalaryController::class,'index']
        );

        Route::get(
            '/dashboard',
            [SalaryController::class,'dashboard']
        );

        Route::post(
            '/',
            [SalaryController::class,'store']
        );

        Route::put(
            '/{salary}',
            [SalaryController::class,'update']
        );

        Route::post(
            '/{salary}/pay',
            [SalaryController::class,'pay']
        );

        Route::delete(
            '/{salary}',
            [SalaryController::class,'destroy']
        );

    });

    Route::post(

        '/salaries/generate',

        [

            SalaryController::class,

            'generate'

        ]

    );
    
    Route::apiResource('price-engine-rules',PriceEngineRuleController::class)->only(['index','update']);
    
    // Units
    Route::get('/units',[UnitController::class,'index']);
    
    // Medicine Units
    Route::get('/medicines/{medicine}/units',[MedicineUnitController::class,'index']);
        
    Route::post('/medicine-units',[MedicineUnitController::class,'store']);
        
    Route::put('/medicine-units/{medicineUnit}',[MedicineUnitController::class,'update']);
        
    Route::delete('/medicine-units/{medicineUnit}',[MedicineUnitController::class,'destroy']);

    // Admin Debts and Shifts
    Route::get('/shifts', [ShiftController::class,'index']);
    Route::get('/debts', [DebtController::class,'index']);
    Route::get('/shifts/{shift}',[ShiftController::class,'show']);
    Route::get('/debts',[DebtController::class,'index']);
    Route::get('/debts/{debt}',[DebtController::class,'show']);
    Route::post('/debts/{debt}/payment',[DebtController::class,'payment']);
    // If using individual routes:
    Route::post('/debts', [DebtController::class, 'store']);

    // OR if you are using apiResource, ensure 'store' isn't excluded:
    Route::apiResource('debts', DebtController::class);
    // ديون الموظفين
    Route::post('/employee-finance/withdraw',);
    Route::post('/shift/withdraw', [ShiftController::class, 'withdraw']);
    Route::post('/shift/debt-payment', [ShiftController::class, 'debtPayment']);
    Route::middleware('auth:sanctum')->group(function () {
    Route::post('/debts/{debt}/payments', [DebtController::class, 'payment']);});
    // المصروفات
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
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
    Route::apiResource('branches', BranchController::class);

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
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{id}', [BranchController::class, 'show']);
    
    // 5. المبيعات (نقطة البيع - POS)
    Route::post('sales', [SaleController::class, 'store']);
    
    Route::prefix('sales')->group(function () {

        Route::get(
            '/medicines',
            [SaleController::class, 'medicines']
        );

    });
    // المستخدمين
    Route::apiResource('users', UserController::class);
//});

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
