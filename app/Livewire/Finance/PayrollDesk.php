<?php

namespace App\Livewire\Finance;

use App\Enums\EmployeeLedgerEntryType;
use App\Models\Employee;
use App\Models\EmployeeLedgerEntry;
use App\Services\EmployeeLedgerService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.desk')]
#[Title('الرواتب — بن مفيد')]
class PayrollDesk extends Component
{
    public string $panel = 'accrual';

    public int $accrualYear;

    public int $accrualMonth;

    public ?int $paymentEmployeeId = null;

    public ?string $paymentAmount = null;

    public string $paymentDate = '';

    public string $paymentNotes = '';

    public ?int $ledgerEmployeeId = null;

    public ?string $savedReference = null;

    public function updatedLedgerEmployeeId(mixed $value): void
    {
        $this->ledgerEmployeeId = filled($value) ? (int) $value : null;
    }

    public function mount(): void
    {
        $this->accrualYear = (int) now()->format('Y');
        $this->accrualMonth = (int) now()->format('n');
        $this->paymentDate = now()->format('Y-m-d');
    }

    public function setPanel(string $panel): void
    {
        $this->panel = $panel;
    }

    public function updatedPaymentEmployeeId(): void
    {
        if ($this->paymentEmployeeId === null) {
            $this->paymentAmount = null;

            return;
        }

        $employee = Employee::query()->find($this->paymentEmployeeId);

        if ($employee !== null) {
            $balance = $employee->balance();
            $this->paymentAmount = $balance > 0 ? (string) $balance : null;
        }
    }

    public function accrueMonth(): void
    {
        $this->validate([
            'accrualYear' => ['required', 'integer', 'min:2020', 'max:2100'],
            'accrualMonth' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            $result = app(EmployeeLedgerService::class)->accrueMonth(
                $this->accrualYear,
                $this->accrualMonth,
                auth()->id(),
            );

            $period = sprintf('%02d/%d', $this->accrualMonth, $this->accrualYear);
            $this->savedReference = "شهر {$period} — سُجّل استحقاق {$result['created']} عامل، تخطّي {$result['skipped']}.";
        } catch (ValidationException $e) {
            $this->addError('accrual', collect($e->errors())->flatten()->first() ?? 'تحقق من البيانات.');
        } catch (\Throwable $e) {
            $this->addError('accrual', $e->getMessage());
        }
    }

    public function paySalary(): void
    {
        $this->validate([
            'paymentEmployeeId' => ['required', 'exists:employees,id'],
            'paymentAmount' => ['required', 'numeric', 'min:0.01'],
            'paymentDate' => ['required', 'date'],
            'paymentNotes' => ['nullable', 'string', 'max:2000'],
        ], [
            'paymentEmployeeId.required' => 'اختر العامل.',
        ]);

        try {
            $employee = Employee::query()->findOrFail($this->paymentEmployeeId);

            $entry = app(EmployeeLedgerService::class)->recordSalaryPayment(
                $employee,
                (float) $this->paymentAmount,
                $this->paymentDate,
                filled($this->paymentNotes) ? $this->paymentNotes : null,
                auth()->id(),
            );

            $this->savedReference = "#{$entry->getKey()} — دفعة راتب {$employee->name} — ".number_format((float) $this->paymentAmount, 2);
            $this->paymentEmployeeId = null;
            $this->paymentAmount = null;
            $this->paymentNotes = '';
            $this->paymentDate = now()->format('Y-m-d');
            $this->resetErrorBag();
        } catch (ValidationException $e) {
            $this->addError('payment', collect($e->errors())->flatten()->first() ?? 'تحقق من البيانات.');
        } catch (\Throwable $e) {
            $this->addError('payment', $e->getMessage());
        }
    }

    public function deleteEntry(int $id): void
    {
        $entry = EmployeeLedgerEntry::query()->findOrFail($id);

        try {
            app(EmployeeLedgerService::class)->deleteEntry($entry);
        } catch (ValidationException $e) {
            $this->addError('ledger', collect($e->errors())->flatten()->first() ?? 'تعذّر الحذف.');
        }
    }

    public function render()
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $accrualPreview = $employees->map(function (Employee $employee) {
            $accrued = EmployeeLedgerEntry::query()
                ->where('employee_id', $employee->getKey())
                ->where('type', EmployeeLedgerEntryType::SalaryAccrual->value)
                ->where('period_year', $this->accrualYear)
                ->where('period_month', $this->accrualMonth)
                ->exists();

            return [
                'employee' => $employee,
                'accrued' => $accrued,
            ];
        });

        $ledgerQuery = EmployeeLedgerEntry::query()
            ->with(['employee', 'user', 'financialMovement'])
            ->latest('entry_date')
            ->latest('id');

        if ($this->ledgerEmployeeId !== null) {
            $ledgerQuery->where('employee_id', $this->ledgerEmployeeId);
        }

        $ledgerEntries = $ledgerQuery->limit(80)->get();

        $ledgerRows = $this->ledgerEmployeeId !== null
            ? app(EmployeeLedgerService::class)->ledgerWithRunningBalance(
                EmployeeLedgerEntry::query()
                    ->where('employee_id', $this->ledgerEmployeeId)
                    ->orderBy('entry_date')
                    ->orderBy('id')
                    ->get()
            )
            : [];

        $employeeSummaries = Employee::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Employee $employee) => [
                'employee' => $employee,
                'balance' => $employee->balance(),
            ]);

        $selectedEmployee = $this->paymentEmployeeId
            ? Employee::query()->find($this->paymentEmployeeId)
            : null;

        $currency = config('store.currency_symbol', '$');

        return view('livewire.finance.payroll-desk', [
            'currency' => $currency,
            'employees' => $employees,
            'employeeOptions' => $employees->map(fn (Employee $employee) => [
                'value' => $employee->id,
                'label' => $employee->name,
                'search' => trim($employee->name.' '.($employee->phone ?? '')),
                'meta' => number_format($employee->balance(), 2).' '.$currency,
            ])->values()->all(),
            'accrualPreview' => $accrualPreview,
            'ledgerEntries' => $ledgerEntries,
            'ledgerRows' => $ledgerRows,
            'employeeSummaries' => $employeeSummaries,
            'selectedEmployee' => $selectedEmployee,
            'monthOptions' => collect(range(1, 12))->map(fn (int $m) => [
                'value' => $m,
                'label' => self::arabicMonthName($m),
            ])->all(),
        ]);
    }

    public static function arabicMonthName(int $month): string
    {
        return [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ][$month] ?? (string) $month;
    }
}
