<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;

class ContactMessageSeeder extends Seeder
{
    public function run(): void
    {
        ContactMessage::query()->firstOrCreate(
            ['name' => 'نورة الحربي', 'message' => 'هل تقدمون خدمة حفلات خاصة في المقهى؟'],
            [
                'phone' => '0506666666',
                'email' => 'noura@example.com',
                'is_read' => false,
            ],
        );

        ContactMessage::query()->firstOrCreate(
            ['name' => 'عبدالرحمن', 'message' => 'أريد معرفة ساعات العمل في أيام الجمعة.'],
            [
                'phone' => '0507777777',
                'email' => 'abdulrahman@example.com',
                'is_read' => true,
            ],
        );
    }
}
