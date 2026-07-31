<?php

namespace App\Livewire\Finance;

use App\Enums\FinancialMovementType;
use App\Livewire\Concerns\BuildsSelectOptions;
use App\Models\FinancialMovement;
use App\Services\FinancialMovementService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.desk')]
#[Title('الحركات المالية — بن مفيد')]
class FinancialMovementsDesk extends Component
{
    use BuildsSelectOptions;

    public string $panel = 'create';

    public string $type = 'expense';

    public string $movementDate = '';

    public ?string $amount = null;

    public string $title = '';

    public string $details = '';

    public ?int $customerId = null;

    public ?int $employeeId = null;

    public string $filterType = '';

    public ?string $savedReference = null;

    public function mount(): void
    {
        $this->movementDate = now()->format('Y-m-d');
    }

    public function updatedType(): void
    {
        if ($this->type !== FinancialMovementType::CustomerPayment->value) {
            $this->customerId = null;
        }

        if ($this->type !== FinancialMovementType::EmployeeAdvance->value) {
            $this->employeeId = null;
        }
    }

    public function setPanel(string $panel): void
    {
        $this->panel = $panel;
    }

    public function save(): void
    {
        $this->validate([
            'type' => ['required', Rule::enum(FinancialMovementType::class)],
            'movementDate' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
            'customerId' => [
                Rule::requiredIf($this->type === FinancialMovementType::CustomerPayment->value),
                'nullable',
                'exists:customers,id',
            ],
            'employeeId' => [
                Rule::requiredIf($this->type === FinancialMovementType::EmployeeAdvance->value),
                'nullable',
                'exists:employees,id',
            ],
        ], [
            'customerId.required' => 'اختر العميل الذي تدفع له.',
            'employeeId.required' => 'اختر العامل الذي تسلّفه.',
            'title.required' => 'أدخل وصفاً مختصراً للحركة.',
        ]);

        try {
            $movement = app(FinancialMovementService::class)->record([
                'type' => $this->type,
                'movement_date' => $this->movementDate,
                'amount' => $this->amount,
                'title' => $this->title,
                'details' => $this->details,
                'customer_id' => $this->customerId,
                'employee_id' => $this->employeeId,
                'user_id' => auth()->id(),
            ]);

            $this->savedReference = '#'.$movement->getKey().' — '.$movement->type->label();
            $this->resetForm();
        } catch (ValidationException $e) {
            $this->addError('save', collect($e->errors())->flatten()->first() ?? 'تحقق من البيانات.');
        } catch (\Throwable $e) {
            $this->addError('save', $e->getMessage());
        }
    }

    public function deleteMovement(int $id): void
    {
        $movement = FinancialMovement::query()->findOrFail($id);
        app(FinancialMovementService::class)->delete($movement);
    }

    public function render()
    {
        $query = FinancialMovement::query()
            ->with(['customer', 'employee', 'user'])
            ->latest('movement_date')
            ->latest('id');

        if ($this->filterType !== '') {
            $query->where('type', $this->filterType);
        }

        return view('livewire.finance.financial-movements-desk', [
            'movements' => $query->limit(60)->get(),
            'currency' => config('store.currency_symbol', '$'),
            'typeOptions' => $this->enumOptions(FinancialMovementType::cases()),
            'customerOptions' => $this->customerOptions(),
            'employeeOptions' => $this->employeeOptions(),
            'totalIncoming' => FinancialMovementService::totalIncoming(),
            'totalOperatingOutgoing' => FinancialMovementService::totalOperatingOutgoing(),
            'totalOwnerWithdrawals' => FinancialMovementService::totalOwnerWithdrawals(),
            'totalOutgoing' => FinancialMovementService::totalOutgoing(),
            'netBalance' => round(FinancialMovementService::totalIncoming() - FinancialMovementService::totalOutgoing(), 2),
        ]);
    }

    private function resetForm(): void
    {
        $this->type = FinancialMovementType::Expense->value;
        $this->movementDate = now()->format('Y-m-d');
        $this->amount = null;
        $this->title = '';
        $this->details = '';
        $this->customerId = null;
        $this->employeeId = null;
        $this->resetErrorBag();
    }
}
