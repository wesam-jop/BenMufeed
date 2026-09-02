<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Services\Storefront\CartService;
use App\Services\Storefront\OpeningHoursService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DeliveryOpeningHoursTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_delivery_is_open_within_configured_hours(): void
    {
        $this->setHours('10:00', '21:00');
        $service = app(OpeningHoursService::class);
        $at = Carbon::parse('2026-08-26 15:00:00', $service->timezone());

        $this->assertTrue($service->isOpenForDelivery($at));
    }

    public function test_delivery_is_closed_outside_configured_hours(): void
    {
        $this->setHours('10:00', '21:00');
        $service = app(OpeningHoursService::class);
        $at = Carbon::parse('2026-08-26 22:30:00', $service->timezone());
        Carbon::setTestNow($at);

        $this->assertFalse($service->isOpenForDelivery());
        $this->assertFalse($service->deliveryStatus()['is_open']);

        try {
            $service->ensureOpenForDelivery();
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('delivery_hours', $e->errors());
        }
    }

    public function test_checkout_otp_is_blocked_when_closed(): void
    {
        $this->setHours('10:00', '21:00');
        $timezone = app(OpeningHoursService::class)->timezone();
        Carbon::setTestNow(Carbon::parse('2026-08-26 23:15:00', $timezone));

        $this->withSession([
            CartService::SESSION_KEY => [
                'items' => [
                    [
                        'product_id' => 1,
                        'name' => 'قهوة',
                        'price' => 10,
                        'image' => null,
                        'quantity' => 1,
                    ],
                ],
                'location_lat' => 24.7136,
                'location_lng' => 46.6753,
                'location_address' => 'الرياض',
                'location_notes' => null,
            ],
        ])->post(route('storefront.checkout.otp'), [
            'name' => 'أحمد',
            'whatsapp' => '0501234567',
        ])->assertSessionHasErrors('delivery_hours');
    }

    private function setHours(string $opens, string $closes): void
    {
        SiteSetting::current()->forceFill([
            'store_opens_at' => $opens,
            'store_closes_at' => $closes,
        ])->save();
    }
}
