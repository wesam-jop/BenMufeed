<?php

namespace Database\Seeders\Concerns;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;

trait InteractsWithMainBranch
{
    protected function mainBranch(): ?Branch
    {
        return Branch::query()->where('code', 'MAIN')->first();
    }

    protected function mainWarehouse(): ?Warehouse
    {
        $branch = $this->mainBranch();

        if ($branch === null) {
            return null;
        }

        return Warehouse::query()
            ->where('branch_id', $branch->id)
            ->where('name', 'المستودع الرئيسي')
            ->first();
    }

    protected function adminUser(): ?User
    {
        return User::query()->where('username', 'admin')->first();
    }

    protected function unit(string $abbreviation): ?Unit
    {
        return Unit::query()->where('abbreviation', $abbreviation)->first();
    }

    protected function cashRegister(): ?CashRegister
    {
        $branch = $this->mainBranch();

        if ($branch === null) {
            return null;
        }

        return CashRegister::query()
            ->where('branch_id', $branch->id)
            ->where('name', 'الصندوق الرئيسي')
            ->first();
    }
}
