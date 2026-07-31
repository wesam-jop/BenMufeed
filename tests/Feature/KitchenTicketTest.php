<?php

namespace Tests\Feature;

use App\Enums\KitchenTicketKind;
use App\Enums\KitchenTicketStatus;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\KitchenTicket;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\KitchenService;
use App\Services\PosService;
use Tests\TestCase;

class KitchenTicketTest extends TestCase
{
    public function test_pos_takeaway_creates_kitchen_ticket(): void
    {
        SiteSetting::current();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-kitchen']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'لاتيه',
            'slug' => 'latte-kitchen',
            'price' => 30,
            'status' => ProductStatus::Published,
        ]);

        app(PosService::class)->placeTakeaway(
            [['product_id' => $product->id, 'quantity' => 1]],
            $admin,
        );

        $ticket = KitchenTicket::query()->first();
        $this->assertNotNull($ticket);
        $this->assertSame(KitchenTicketKind::TakeawayPos, $ticket->kind);
        $this->assertSame(KitchenTicketStatus::Pending, $ticket->status);
        $this->assertSame(1, $ticket->orderItems()->count());
    }

    public function test_table_append_creates_append_kitchen_ticket(): void
    {
        SiteSetting::current();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-append']);

        $productA = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'شاي',
            'slug' => 'tea-append',
            'price' => 10,
            'status' => ProductStatus::Published,
        ]);

        $productB = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'كيك',
            'slug' => 'cake-append',
            'price' => 20,
            'status' => ProductStatus::Published,
        ]);

        RestaurantTable::query()->create([
            'number' => 4,
            'qr_token' => 'kitchen-append-token-123456789012345678901234',
            'is_active' => true,
        ]);

        $pos = app(PosService::class);
        $pos->sendToTable([['product_id' => $productA->id, 'quantity' => 1]], $admin, 4);
        $pos->sendToTable([['product_id' => $productB->id, 'quantity' => 2]], $admin, 4);

        $this->assertDatabaseCount('kitchen_tickets', 2);

        $append = KitchenTicket::query()->where('kind', KitchenTicketKind::TableAppend)->first();
        $this->assertNotNull($append);
        $this->assertSame(1, $append->orderItems()->count());
        $this->assertSame('كيك', $append->orderItems()->first()->product_name);
    }

    public function test_kitchen_route_is_removed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/kitchen')->assertNotFound();
    }

    public function test_kitchen_ticket_print_view(): void
    {
        SiteSetting::current();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-print']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'موكا',
            'slug' => 'mocha-print',
            'price' => 35,
            'status' => ProductStatus::Published,
        ]);

        app(PosService::class)->placeTakeaway(
            [['product_id' => $product->id, 'quantity' => 1]],
            $admin,
        );

        $ticket = KitchenTicket::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('print.kitchen-ticket', $ticket))
            ->assertOk()
            ->assertSee('موكا');

        $ticket->refresh();
        $this->assertNotNull($ticket->printed_at);
    }

    public function test_kitchen_ticket_silent_view_does_not_mark_printed(): void
    {
        SiteSetting::current();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-silent-kitchen']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'اسبرesso',
            'slug' => 'espresso-silent',
            'price' => 25,
            'status' => ProductStatus::Published,
        ]);

        app(PosService::class)->placeTakeaway(
            [['product_id' => $product->id, 'quantity' => 1]],
            $admin,
        );

        $ticket = KitchenTicket::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('print.kitchen-ticket', ['ticket' => $ticket, 'silent' => 1]))
            ->assertOk();

        $ticket->refresh();
        $this->assertNull($ticket->printed_at);
    }

    public function test_mark_ready_updates_ticket(): void
    {
        SiteSetting::current();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-ready']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'عصير',
            'slug' => 'juice-ready',
            'price' => 15,
            'status' => ProductStatus::Published,
        ]);

        app(PosService::class)->placeTakeaway(
            [['product_id' => $product->id, 'quantity' => 1]],
            $admin,
        );

        $ticket = KitchenTicket::query()->firstOrFail();
        app(KitchenService::class)->markReady($ticket);

        $ticket->refresh();
        $this->assertSame(KitchenTicketStatus::Ready, $ticket->status);
        $this->assertNotNull($ticket->ready_at);
    }
}
