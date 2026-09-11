<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::query()->firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'بن مفيد — الفرع الرئيسي',
                'address' => 'دمشق، سوريا',
                'phone' => '+963985605918',
                'email' => 'info@benmufeed.com',
                'timezone' => 'Asia/Damascus',
                'currency' => 'USD',
                'is_active' => true,
            ],
        );
    }
}
