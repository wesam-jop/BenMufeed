<?php

namespace App\Jobs;

use App\Services\SalesAggregateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshDailySalesSummary implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $saleDate,
    ) {}

    public function handle(SalesAggregateService $aggregates): void
    {
        $aggregates->persistDailySummary($this->saleDate);
    }
}
