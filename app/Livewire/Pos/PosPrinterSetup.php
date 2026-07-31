<?php

namespace App\Livewire\Pos;

use App\Models\PosSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.pos')]
#[Title('ربط الطابعات — بن مفيد')]
class PosPrinterSetup extends Component
{
    public ?string $kitchen_printer_name = null;

    public ?string $cashier_printer_name = null;

    public bool $auto_print_kitchen = true;

    public bool $auto_print_cashier = true;

    public bool $kitchen_alert_sound = true;

    public int $receipt_paper_width_mm = 80;

    public function mount(): void
    {
        $settings = PosSetting::current();

        $this->kitchen_printer_name = $settings->kitchen_printer_name;
        $this->cashier_printer_name = $settings->cashier_printer_name;
        $this->auto_print_kitchen = (bool) $settings->auto_print_kitchen;
        $this->auto_print_cashier = (bool) $settings->auto_print_cashier;
        $this->kitchen_alert_sound = (bool) $settings->kitchen_alert_sound;
        $this->receipt_paper_width_mm = (int) $settings->receipt_paper_width_mm ?: 80;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'kitchen_printer_name' => ['nullable', 'string', 'max:255'],
            'cashier_printer_name' => ['nullable', 'string', 'max:255'],
            'auto_print_kitchen' => ['boolean'],
            'auto_print_cashier' => ['boolean'],
            'kitchen_alert_sound' => ['boolean'],
            'receipt_paper_width_mm' => ['required', 'integer', 'in:58,80'],
        ]);

        PosSetting::current()->update($validated);

        $this->dispatch('pos-print-settings-saved');

        session()->flash('print_setup_ok', 'تم حفظ إعدادات الطباعة — الطلبات ستُطبَع تلقائياً عند القبول أو البيع.');
    }

    public function render()
    {
        return view('livewire.pos.printer-setup');
    }
}
