<?php

namespace App\Listeners;

use App\Events\OrderCompleted;
use App\Events\RestaurantMetricsChanged;
use App\Events\WebsiteOrderPlaced;
use App\Jobs\NotifyAdminsWebsiteOrder;
use App\Jobs\RefreshDailySalesSummary;
use App\Jobs\SendCustomerDeliveryOrderPendingWhatsApp;
use App\Jobs\SendStoreNewDeliveryOrderWhatsApp;
use App\Support\RestaurantMetricsCache;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Carbon;

class InvalidateRestaurantMetrics implements ShouldQueueAfterCommit
{
    public function handleOrderCompleted(OrderCompleted $event): void
    {
        $date = $event->saleDate ?? now()->toDateString();

        RestaurantMetricsCache::forgetAll($date);
        \App\Services\ProductSalesService::forgetCache();

        RefreshDailySalesSummary::dispatch($date);
    }

    public function handleWebsiteOrderPlaced(WebsiteOrderPlaced $event): void
    {
        RestaurantMetricsCache::forgetAll();
        \App\Services\ProductSalesService::forgetCache();

        NotifyAdminsWebsiteOrder::dispatch($event->orderId);
        SendStoreNewDeliveryOrderWhatsApp::dispatch($event->orderId);
        SendCustomerDeliveryOrderPendingWhatsApp::dispatch($event->orderId);
    }

    public function handleMetricsChanged(RestaurantMetricsChanged $event): void
    {
        $date = $event->saleDate ?? now()->toDateString();

        RestaurantMetricsCache::forgetAll($date);
        \App\Services\ProductSalesService::forgetCache();

        if ($event->saleDate !== null) {
            RefreshDailySalesSummary::dispatch($date);
        }
    }

    public function subscribe($events): array
    {
        return [
            OrderCompleted::class => 'handleOrderCompleted',
            WebsiteOrderPlaced::class => 'handleWebsiteOrderPlaced',
            RestaurantMetricsChanged::class => 'handleMetricsChanged',
        ];
    }
}
