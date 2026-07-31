<?php

namespace App\Data;

use App\Models\KitchenTicket;
use App\Models\Order;

final class WebsiteOrderAcceptance
{
    public function __construct(
        public Order $order,
        public ?KitchenTicket $kitchenTicket,
    ) {}
}
