<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            UserSeeder::class,
            MedicineSeeder::class,
            MedicineBatchSeeder::class,
            PurchaseSeeder::class,
            SaleSeeder::class,
            RefundSeeder::class,
        ]);
    }
}
