<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dashboard / profits / daily-sales caches should refresh.
 */
class RestaurantMetricsChanged
{
    use Dispatchable;

    public function __construct(
        public ?string $saleDate = null,
    ) {}
}
