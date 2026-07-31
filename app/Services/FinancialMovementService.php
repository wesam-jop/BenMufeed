<?php

namespace App\Services;

use App\Enums\FinancialMovementType;
use App\Models\Employee;
use App\Models\FinancialMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialMovementService
{
    public function __construct(
        protected EmployeeLedgerService $employeeLedger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): FinancialMovement
    {
        return DB::transaction(function () use ($data): FinancialMovement {
            $type = $data['type'] instanceof FinancialMovementType
                ? $data['type']
                : FinancialMovementType::from((string) $data['type']);

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'المبلغ يجب أن يكون أكبر من صفر.',
                ]);
            }

            $this->validateForType($type, $data);

            $movement = FinancialMovement::query()->create([
                'type' => $type,
                'amount' => $amount,
                'movement_date' => $data['movement_date'],
                'title' => $data['title'],
                'details' => filled($data['details'] ?? null) ? $data['details'] : null,
                'customer_id' => $data['customer_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
            ])->fresh(['customer', 'employee', 'user']);

            if ($type === FinancialMovementType::EmployeeAdvance) {
                $employee = Employee::query()->findOrFail($data['employee_id']);
                $this->employeeLedger->recordAdvance($employee, $movement);
            }

            return $movement;
        });
    }

    public function delete(FinancialMovement $movement): void
    {
        DB::transaction(function () use ($movement): void {
            $movement->employeeLedgerEntry?->delete();
            $movement->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateForType(FinancialMovementType $type, array $data): void
    {
        if ($type === FinancialMovementType::CustomerPayment && empty($data['customer_id'])) {
            throw ValidationException::withMessages([
                'customerId' => 'اختر العميل الذي تدفع له.',
            ]);
        }

        if ($type === FinancialMovementType::EmployeeAdvance && empty($data['employee_id'])) {
            throw ValidationException::withMessages([
                'employeeId' => 'اختر العامل الذي تسلّفه.',
            ]);
        }
    }

    public static function totalIncoming(): float
    {
        return round((float) FinancialMovement::query()
            ->where('type', FinancialMovementType::ExternalReceipt->value)
            ->sum('amount'), 2);
    }

    public static function totalOperatingOutgoing(): float
    {
        return round((float) FinancialMovement::query()
            ->whereIn('type', [
                FinancialMovementType::Expense->value,
                FinancialMovementType::CustomerPayment->value,
                FinancialMovementType::EmployeeAdvance->value,
            ])
            ->sum('amount'), 2);
    }

    public static function totalOwnerWithdrawals(): float
    {
        return round((float) FinancialMovement::query()
            ->where('type', FinancialMovementType::OwnerWithdrawal->value)
            ->sum('amount'), 2);
    }

    public static function totalOutgoing(): float
    {
        return round(self::totalOperatingOutgoing() + self::totalOwnerWithdrawals(), 2);
    }
}
