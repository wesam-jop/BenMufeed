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
                'name' => 'الفرع الرئيسي',
                'address' => 'الرياض، المملكة العربية السعودية',
                'phone' => '+966500000000',
                'email' => 'info@kasher.local',
                'timezone' => 'Asia/Riyadh',
                'currency' => 'SAR',
                'is_active' => true,
            ],
        );
    }
}
