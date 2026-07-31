<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_accounts_seeder_creates_only_admin_user(): void
    {
        $this->seed(AdminAccountsSeeder::class);

        $this->assertSame(1, User::query()->count());

        $admin = User::query()->where('email', AdminAccountsSeeder::ADMIN_EMAIL)->first();

        $this->assertNotNull($admin);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue($admin->userRole()->canAccessPanel());
        $this->assertTrue(Hash::check('password', $admin->password));
    }

    public function test_admin_accounts_seeder_is_idempotent(): void
    {
        $this->seed(AdminAccountsSeeder::class);
        $this->seed(AdminAccountsSeeder::class);

        $this->assertSame(1, User::query()->count());
    }
}
