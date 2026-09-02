<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'MAIN')->first();

        $admin = User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@kasher.local',
                'password' => 'password',
                'branch_id' => $branch?->id,
                'is_super_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->syncPermissions(Permission::query()->where('guard_name', 'web')->pluck('name'));

        $adminRole = \Spatie\Permission\Models\Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first();

        if ($adminRole !== null) {
            $admin->syncRoles([$adminRole]);
        }
    }
}
