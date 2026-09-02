<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * @var list<array{phone: string, name: string, email: string, points: int}>
     */
    private const CUSTOMERS = [
        ['phone' => '0501111111', 'name' => 'أحمد محمد', 'email' => 'ahmed@example.com', 'points' => 120],
        ['phone' => '0502222222', 'name' => 'سارة علي', 'email' => 'sara@example.com', 'points' => 85],
        ['phone' => '0503333333', 'name' => 'خالد عبدالله', 'email' => 'khaled@example.com', 'points' => 40],
    ];

    public function run(): void
    {
        foreach (self::CUSTOMERS as $customer) {
            Customer::query()->updateOrCreate(
                ['phone' => $customer['phone']],
                [
                    'name' => $customer['name'],
                    'email' => $customer['email'],
                    'password' => 'password',
                    'loyalty_points' => $customer['points'],
                    'is_active' => true,
                ],
            );
        }
    }
}
