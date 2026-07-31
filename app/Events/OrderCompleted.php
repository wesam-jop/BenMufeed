<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $orderId,
        public ?string $saleDate = null,
    ) {}
}
