<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_page_loads(): void
    {
        $this->get(route('storefront.contact'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('storefront/contact'));
    }

    public function test_contact_page_exposes_social_channels(): void
    {
        SiteSetting::query()->firstOrCreate([], SiteSetting::defaults());
        SiteSetting::current()->update([
            'whatsapp' => '966501234567',
            'facebook' => 'kashercafe',
            'instagram' => '@kasher',
            'contact_title' => 'تواصل معنا',
        ]);

        $this->get(route('storefront.contact'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('storefront/contact')
                ->where('business.whatsapp', '966501234567')
                ->where('business.facebook', 'kashercafe')
                ->where('business.instagram', '@kasher')
            );
    }

    public function test_contact_form_endpoint_is_removed(): void
    {
        $this->post('/contact', [
            'name' => 'سارة',
            'phone' => '0501234567',
            'message' => 'رسالة',
        ])->assertMethodNotAllowed();
    }
}
