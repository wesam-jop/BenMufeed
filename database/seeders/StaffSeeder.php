<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class StaffSeeder extends Seeder
{
    use InteractsWithMainBranch;

    /**
     * @var list<array{username: string, name: string, email: string, role: string}>
     */
    private const STAFF = [
        ['username' => 'manager', 'name' => 'مدير الفرع', 'email' => 'manager@kasher.local', 'role' => 'manager'],
        ['username' => 'cashier', 'name' => 'أمين الصندوق', 'email' => 'cashier@kasher.local', 'role' => 'cashier'],
        ['username' => 'kitchen', 'name' => 'موظف المطبخ', 'email' => 'kitchen@kasher.local', 'role' => 'kitchen'],
    ];

    public function run(): void
    {
        $branch = $this->mainBranch();

        foreach (self::STAFF as $staff) {
            $user = User::query()->updateOrCreate(
                ['username' => $staff['username']],
                [
                    'name' => $staff['name'],
                    'email' => $staff['email'],
                    'password' => 'password',
                    'branch_id' => $branch?->id,
                    'is_super_admin' => false,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
            );

            $role = Role::query()->where('name', $staff['role'])->where('guard_name', 'web')->first();

            if ($role !== null) {
                $user->syncRoles([$role]);
            }
        }
    }
}
