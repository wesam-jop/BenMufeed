<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CashRegister;
use Illuminate\Database\Seeder;

class CashRegisterSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'MAIN')->first();

        if ($branch === null) {
            return;
        }

        CashRegister::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'name' => 'الصندوق الرئيسي',
            ],
            [
                'is_active' => true,
            ],
        );
    }
}
