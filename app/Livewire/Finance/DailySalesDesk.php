<?php

namespace App\Livewire\Finance;

use App\Services\DailySalesService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.desk')]
#[Title('المبيعات — جرد يومي')]
class DailySalesDesk extends Component
{
    public string $date = '';

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function goToPreviousDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->format('Y-m-d');
    }

    public function goToNextDay(): void
    {
        $this->date = Carbon::parse($this->date)->addDay()->format('Y-m-d');
    }

    public function goToToday(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function render()
    {
        $report = app(DailySalesService::class)->report(
            filled($this->date) ? $this->date : now()->format('Y-m-d'),
        );

        return view('livewire.finance.daily-sales-desk', [
            'currency' => config('store.currency_symbol', '$'),
            'report' => $report,
            'isToday' => $this->date === now()->format('Y-m-d'),
        ]);
    }
}
