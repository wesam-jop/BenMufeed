<?php

namespace App\Console\Commands;

use Database\Seeders\AdminAccountsSeeder;
use Illuminate\Console\Command;

class EnsureSystemUsersCommand extends Command
{
    protected $signature = 'benmufeed:ensure-users
                            {--password=password : كلمة المرور لحساب المدير الافتراضي}';

    protected $description = 'إنشاء أو تحديث حساب المدير — آمن للتشغيل المتكرر';

    public function handle(): int
    {
        if ($this->option('password') !== AdminAccountsSeeder::DEFAULT_PASSWORD) {
            $this->warn('تخصيص كلمة المرور من الأمر غير مدعوم بعد — يُستخدم: '.AdminAccountsSeeder::DEFAULT_PASSWORD);
        }

        $this->call('db:seed', [
            '--class' => AdminAccountsSeeder::class,
            '--force' => true,
        ]);

        return self::SUCCESS;
    }
}
