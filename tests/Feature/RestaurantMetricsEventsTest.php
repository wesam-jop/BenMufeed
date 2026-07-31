<?php

namespace Tests\Feature;

use App\Events\OrderCompleted;
use App\Events\WebsiteOrderPlaced;
use App\Jobs\NotifyAdminsWebsiteOrder;
use App\Jobs\RefreshDailySalesSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RestaurantMetricsEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_completed_dispatches_metrics_refresh_job(): void
    {
        Bus::fake([RefreshDailySalesSummary::class, NotifyAdminsWebsiteOrder::class]);

        OrderCompleted::dispatch(1, now()->toDateString());

        Bus::assertDispatched(RefreshDailySalesSummary::class);
    }

    public function test_website_order_placed_notifies_admins_via_queue(): void
    {
        Bus::fake([NotifyAdminsWebsiteOrder::class, RefreshDailySalesSummary::class]);

        WebsiteOrderPlaced::dispatch(99);

        Bus::assertDispatched(NotifyAdminsWebsiteOrder::class, fn (NotifyAdminsWebsiteOrder $job): bool => $job->orderId === 99);
    }

    public function test_event_subscriber_is_registered(): void
    {
        $this->assertTrue(
            Event::hasListeners(OrderCompleted::class),
        );
    }
}
