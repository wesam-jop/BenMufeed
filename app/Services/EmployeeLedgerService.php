<?php

namespace App\Services;

use App\Enums\EmployeeLedgerEntryType;
use App\Enums\FinancialMovementType;
use App\Models\Employee;
use App\Models\EmployeeLedgerEntry;
use App\Models\FinancialMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeLedgerService
{
    public static function balanceFor(int $employeeId): float
    {
        $credit = (float) EmployeeLedgerEntry::query()
            ->where('employee_id', $employeeId)
            ->where('type', EmployeeLedgerEntryType::SalaryAccrual->value)
            ->sum('amount');

        $debit = (float) EmployeeLedgerEntry::query()
            ->where('employee_id', $employeeId)
            ->whereIn('type', [
                EmployeeLedgerEntryType::Advance->value,
                EmployeeLedgerEntryType::SalaryPayment->value,
            ])
            ->sum('amount');

        return round($credit - $debit, 2);
    }

    /**
     * @return array{created: int, skipped: int, employees: Collection<int, Employee>}
     */
    public function accrueMonth(int $year, int $month, ?int $userId = null): array
    {
        if ($month < 1 || $month > 12) {
            throw ValidationException::withMessages([
                'periodMonth' => 'اختر شهراً صالحاً.',
            ]);
        }

        $created = 0;
        $skipped = 0;
        $employees = Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        DB::transaction(function () use ($employees, $year, $month, $userId, &$created, &$skipped): void {
            foreach ($employees as $employee) {
                if ((float) $employee->monthly_salary <= 0) {
                    $skipped++;

                    continue;
                }

                $exists = EmployeeLedgerEntry::query()
                    ->where('employee_id', $employee->getKey())
                    ->where('type', EmployeeLedgerEntryType::SalaryAccrual->value)
                    ->where('period_year', $year)
                    ->where('period_month', $month)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                EmployeeLedgerEntry::query()->create([
                    'employee_id' => $employee->getKey(),
                    'type' => EmployeeLedgerEntryType::SalaryAccrual,
                    'amount' => $employee->monthly_salary,
                    'entry_date' => sprintf('%04d-%02d-01', $year, $month),
                    'period_year' => $year,
                    'period_month' => $month,
                    'notes' => 'استحقاق راتب '.sprintf('%02d/%d', $month, $year),
                    'user_id' => $userId,
                ]);

                $created++;
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'employees' => $employees,
        ];
    }

    public function recordAdvance(Employee $employee, FinancialMovement $movement): EmployeeLedgerEntry
    {
        return EmployeeLedgerEntry::query()->create([
            'employee_id' => $employee->getKey(),
            'type' => EmployeeLedgerEntryType::Advance,
            'amount' => $movement->amount,
            'entry_date' => $movement->movement_date,
            'financial_movement_id' => $movement->getKey(),
            'notes' => $movement->details ?? $movement->title,
            'user_id' => $movement->user_id,
        ]);
    }

    public function recordSalaryPayment(
        Employee $employee,
        float $amount,
        string $entryDate,
        ?string $notes = null,
        ?int $userId = null,
    ): EmployeeLedgerEntry {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'paymentAmount' => 'المبلغ يجب أن يكون أكبر من صفر.',
            ]);
        }

        return DB::transaction(function () use ($employee, $amount, $entryDate, $notes, $userId): EmployeeLedgerEntry {
            $balance = self::balanceFor($employee->getKey());

            if ($amount > $balance) {
                throw ValidationException::withMessages([
                    'paymentAmount' => "المبلغ أكبر من المتبقي للعامل ({$balance}).",
                ]);
            }

            $movement = FinancialMovement::query()->create([
                'type' => FinancialMovementType::Expense,
                'amount' => $amount,
                'movement_date' => $entryDate,
                'title' => 'دفعة راتب — '.$employee->name,
                'details' => filled($notes) ? $notes : null,
                'employee_id' => $employee->getKey(),
                'user_id' => $userId,
            ]);

            return EmployeeLedgerEntry::query()->create([
                'employee_id' => $employee->getKey(),
                'type' => EmployeeLedgerEntryType::SalaryPayment,
                'amount' => $amount,
                'entry_date' => $entryDate,
                'financial_movement_id' => $movement->getKey(),
                'notes' => filled($notes) ? $notes : 'دفعة راتب',
                'user_id' => $userId,
            ]);
        });
    }

    public function deleteEntry(EmployeeLedgerEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            if ($entry->type === EmployeeLedgerEntryType::Advance) {
                throw ValidationException::withMessages([
                    'entry' => 'احذف السلفة من الحركات المالية.',
                ]);
            }

            if ($entry->financial_movement_id !== null) {
                $movement = FinancialMovement::query()->find($entry->financial_movement_id);

                if ($movement !== null) {
                    app(FinancialMovementService::class)->delete($movement);

                    return;
                }
            }

            $entry->delete();
        });
    }

    /**
     * @return array<int, array{entry: EmployeeLedgerEntry, balance: float}>
     */
    public function ledgerWithRunningBalance(Collection $entries): array
    {
        $running = 0.0;
        $rows = [];

        foreach ($entries->sortBy(['entry_date', 'id']) as $entry) {
            $running = round($running + $entry->signedAmount(), 2);
            $rows[] = [
                'entry' => $entry,
                'balance' => $running,
            ];
        }

        return $rows;
    }
}
