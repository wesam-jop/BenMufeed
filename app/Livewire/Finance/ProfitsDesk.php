<?php

namespace App\Livewire\Finance;

use App\Services\ProfitsService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.desk')]
#[Title('الأرباح — بن مفيد')]
class ProfitsDesk extends Component
{
    public string $from = '';

    public string $to = '';

    public string $preset = 'month';

    public function mount(): void
    {
        $this->applyPreset('month');
    }

    public function updatedFrom(): void
    {
        $this->preset = 'custom';
    }

    public function updatedTo(): void
    {
        $this->preset = 'custom';
    }

    public function applyPreset(string $preset): void
    {
        $this->preset = $preset;

        match ($preset) {
            'today' => $this->setRange(now(), now()),
            'month' => $this->setRange(now()->startOfMonth(), now()),
            'last_month' => $this->setRange(
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ),
            'year' => $this->setRange(now()->startOfYear(), now()),
            'all' => $this->setRange(null, null),
            default => null,
        };
    }

    public function render()
    {
        $summary = app(ProfitsService::class)->summary(
            filled($this->from) ? $this->from : null,
            filled($this->to) ? $this->to : null,
        );

        return view('livewire.finance.profits-desk', [
            'currency' => config('store.currency_symbol', '$'),
            'summary' => $summary,
        ]);
    }

    protected function setRange(?Carbon $from, ?Carbon $to): void
    {
        $this->from = $from?->format('Y-m-d') ?? '';
        $this->to = $to?->format('Y-m-d') ?? '';
    }
}
