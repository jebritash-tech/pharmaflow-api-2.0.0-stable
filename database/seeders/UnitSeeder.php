<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Unit::insert([
            [
                'name' => 'Piece',
                'symbol' => 'pc'
            ],
            [
                'name' => 'Strip',
                'symbol' => 'str'
            ],
            [
                'name' => 'Box',
                'symbol' => 'box'
            ],
            [
                'name' => 'Bottle',
                'symbol' => 'bot'
            ],
            [
                'name' => 'Vial',
                'symbol' => 'vial'
            ],
            [
                'name' => 'Tube',
                'symbol' => 'tube'
            ],
            [
                'name' => 'Sachet',
                'symbol' => 'sach'
            ],
            [
                'name' => 'Pack',
                'symbol' => 'pack'
            ]
        ]);
    }
}
