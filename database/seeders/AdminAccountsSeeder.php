<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * حساب المدير فقط (role=admin, is_admin=true).
 *
 * تشغيل: php artisan db:seed
 * أو:    php artisan db:seed --class=AdminAccountsSeeder
 * أو:    php artisan benmufeed:ensure-admin
 */
class AdminAccountsSeeder extends Seeder
{
    public const DEFAULT_PASSWORD = 'password';

    public const ADMIN_EMAIL = 'admin@example.com';

    public const ADMIN_NAME = 'مدير بن مفيد';

    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => self::ADMIN_NAME,
                'password' => self::DEFAULT_PASSWORD,
                'role' => UserRole::Admin,
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->command?->newLine();
        $this->command?->info('═══ حساب المدير ═══');
        $this->command?->line('  • '.self::ADMIN_EMAIL.' — '.self::ADMIN_NAME);
        $this->command?->info('كلمة المرور: '.self::DEFAULT_PASSWORD);
        $this->command?->info('تسجيل الدخول: /login ← يُوجَّه تلقائياً إلى /admin');
        $this->command?->newLine();
    }
}
