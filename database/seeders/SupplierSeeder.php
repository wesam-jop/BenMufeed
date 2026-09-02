<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::query()->firstOrCreate(
            ['name' => 'مورد القهوة المتخصص'],
            [
                'contact_person' => 'محمد العلي',
                'phone' => '0504444444',
                'email' => 'coffee-supplier@example.com',
                'address' => 'الرياض، حي العليا',
                'tax_number' => '300123456789003',
                'balance' => 0,
                'is_active' => true,
            ],
        );

        Supplier::query()->firstOrCreate(
            ['name' => 'شركة الألبان'],
            [
                'contact_person' => 'فهد السalem',
                'phone' => '0505555555',
                'email' => 'dairy@example.com',
                'address' => 'الرياض، المنطقة الصناعية',
                'tax_number' => '300987654321003',
                'balance' => 0,
                'is_active' => true,
            ],
        );
    }
}
