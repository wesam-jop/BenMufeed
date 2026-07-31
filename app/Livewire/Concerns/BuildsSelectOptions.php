<?php

namespace App\Livewire\Concerns;

use App\Models\Customer;
use App\Models\Employee;

trait BuildsSelectOptions
{
    /**
     * @return array<int, array{value: int, label: string, search: string, meta: string}>
     */
    protected function customerOptions(): array
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn (Customer $customer) => [
                'value' => $customer->id,
                'label' => $customer->name,
                'search' => trim($customer->name.' '.($customer->phone ?? '')),
                'meta' => $customer->phone ? (string) $customer->phone : '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string, search: string, meta: string}>
     */
    protected function employeeOptions(): array
    {
        return Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'monthly_salary'])
            ->map(fn (Employee $employee) => [
                'value' => $employee->id,
                'label' => $employee->name,
                'search' => trim($employee->name.' '.($employee->phone ?? '')),
                'meta' => $employee->phone ? (string) $employee->phone : '',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, search: string, meta?: string}>
     */
    protected function enumOptions(array $cases): array
    {
        return collect($cases)
            ->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'search' => $case->label(),
            ])
            ->values()
            ->all();
    }
}
