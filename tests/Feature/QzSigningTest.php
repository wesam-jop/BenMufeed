<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\QzSigningService;
use Tests\TestCase;

class QzSigningTest extends TestCase
{
    public function test_qz_routes_require_staff(): void
    {
        $cashier = User::factory()->create(['role' => \App\Enums\UserRole::Cashier, 'is_admin' => false]);
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin, 'is_admin' => true]);

        $this->get('/qz/certificate')->assertRedirect();
        $this->actingAs($cashier)->get('/qz/certificate')->assertOk();
        $this->actingAs($admin)->get('/qz/certificate')->assertOk();
    }

    public function test_qz_certificate_and_sign_endpoints(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $qz = app(QzSigningService::class);

        $this->actingAs($admin)
            ->get('/qz/certificate')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $certificate = $qz->certificate();
        $this->assertStringContainsString('BEGIN CERTIFICATE', $certificate);

        $payload = 'sample-qz-request';
        $signature = $this->actingAs($admin)
            ->call('POST', '/qz/sign', content: $payload, server: ['CONTENT_TYPE' => 'text/plain'])
            ->assertOk()
            ->getContent();

        $this->assertNotSame('', $signature);
        $this->assertNotFalse(base64_decode($signature, true));
    }

    public function test_print_views_support_silent_mode(): void
    {
        SiteSetting::current();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = \App\Models\Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-silent']);

        $product = \App\Models\Product::query()->create([
            'category_id' => $category->id,
            'name' => 'موكا',
            'slug' => 'mocha-silent',
            'price' => 35,
            'status' => \App\Enums\ProductStatus::Published,
        ]);

        app(\App\Services\PosService::class)->placeTakeaway(
            [['product_id' => $product->id, 'quantity' => 1]],
            $admin,
        );

        $ticket = \App\Models\KitchenTicket::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('print.kitchen-ticket', ['ticket' => $ticket, 'silent' => 1]))
            ->assertOk()
            ->assertDontSee('onload="window.print()"', false);
    }
}
