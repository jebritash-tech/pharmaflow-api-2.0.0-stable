<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'مسكنات',
            'مضادات حيوية',
            'فيتامينات',
            'أطفال',
            'ضغط',
            'سكري',
            'تجميل'
        ];

        foreach ($categories as $name) {

            DB::table('categories')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now()
            ]);

        }
    }
}
