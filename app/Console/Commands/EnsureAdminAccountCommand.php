<?php

namespace App\Console\Commands;

use Database\Seeders\AdminAccountsSeeder;
use Illuminate\Console\Command;

class EnsureAdminAccountCommand extends Command
{
    protected $signature = 'benmufeed:ensure-admin
                            {--password=password : كلمة مرور المدير الافتراضية}';

    protected $description = 'إنشاء أو تحديث حساب المدير فقط (صلاحية admin — لوحة /admin)';

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
