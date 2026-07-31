<?php

namespace App\Livewire\Pos;

use App\Models\PosSetting;
use App\Services\PosService;
use App\Support\DispatchesPrintJobs;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pos')]
#[Title('الطاولات — نقطة البيع')]
class PosTables extends Component
{
    use DispatchesPrintJobs;

    public ?int $closingTableNumber = null;

    public ?string $closingPaidAmount = null;

    public ?string $successMessage = null;

    public function openCloseTable(int $tableNumber): void
    {
        $order = app(PosService::class)->openTables()->firstWhere('table_number', $tableNumber);
        $this->closingTableNumber = $tableNumber;
        $this->closingPaidAmount = $order ? (string) $order->total : null;
        $this->resetErrorBag();
    }

    public function closeTable(): void
    {
        if ($this->closingTableNumber === null) {
            return;
        }

        $this->validate([
            'closingPaidAmount' => ['required', 'numeric', 'min:0'],
        ], [
            'closingPaidAmount.required' => 'أدخل المبلغ المقبوض من الزبون.',
        ]);

        try {
            $order = app(PosService::class)->findOpenTableOrder($this->closingTableNumber);
            $orderId = $order?->getKey();

            app(PosService::class)->closeTable(
                $this->closingTableNumber,
                (float) $this->closingPaidAmount,
                auth()->user(),
            );

            $this->successMessage = 'تم إغلاق طاولة '.$this->closingTableNumber.' — مقبوض '.number_format((float) $this->closingPaidAmount, 2);
            $this->closingTableNumber = null;
            $this->closingPaidAmount = null;

            if (PosSetting::current()->auto_print_cashier && $orderId !== null) {
                $this->dispatchCashierPrint($orderId);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->addError('close', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->addError('close', $e->getMessage());
        }
    }

    public function cancelCloseTable(): void
    {
        $this->closingTableNumber = null;
        $this->closingPaidAmount = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $pos = app(PosService::class);
        $openTables = $pos->openTables();

        return view('livewire.pos.tables', [
            'currency' => config('store.currency_symbol', '$'),
            'openTables' => $openTables,
            'openTablesCount' => $openTables->count(),
            'closingOrder' => $this->closingTableNumber
                ? $openTables->firstWhere('table_number', $this->closingTableNumber)
                : null,
        ]);
    }
}
