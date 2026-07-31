<?php

namespace Tests\Feature;

use App\Enums\PrintJobStatus;
use App\Models\Category;
use App\Models\KitchenTicket;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PosService;
use App\Services\PrintJobService;
use Tests\TestCase;

class PrintJobTest extends TestCase
{
    public function test_print_log_page_requires_staff(): void
    {
        $cashier = User::factory()->create(['role' => \App\Enums\UserRole::Cashier, 'is_admin' => false]);
        $kitchen = User::factory()->create(['role' => \App\Enums\UserRole::Kitchen, 'is_admin' => false]);

        $this->get('/pos/print-log')->assertRedirect();
        $this->actingAs($cashier)->get('/pos/print-log')->assertOk()->assertSee('سجل الطباعة');
        $this->actingAs($kitchen)->get('/pos/print-log')->assertRedirect();
    }

    public function test_queue_kitchen_job_and_report_lifecycle(): void
    {
        SiteSetting::current();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-print-job']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'موكا',
            'slug' => 'mocha-print-job',
            'price' => 35,
            'status' => \App\Enums\ProductStatus::Published,
        ]);

        app(PosService::class)->placeTakeaway(
            [['product_id' => $product->id, 'quantity' => 1]],
            $admin,
        );

        $ticket = KitchenTicket::query()->firstOrFail();
        $jobs = app(PrintJobService::class);
        $job = $jobs->queueKitchen($ticket->getKey(), 'manual');

        $this->actingAs($admin)
            ->postJson(route('print.jobs.printing', $job))
            ->assertOk();

        $this->actingAs($admin)
            ->postJson(route('print.jobs.succeeded', $job))
            ->assertOk();

        $job->refresh();
        $this->assertSame(PrintJobStatus::Succeeded, $job->status);
        $this->assertNotNull($job->succeeded_at);
        $ticket->refresh();
        $this->assertNotNull($ticket->printed_at);
    }

    public function test_silent_kitchen_print_does_not_mark_printed_until_success(): void
    {
        SiteSetting::current();

        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::query()->create(['name' => 'مشروبات', 'slug' => 'drinks-silent-job']);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'شاي',
            'slug' => 'tea-silent-job',
            'price' => 10,
            'status' => \App\Enums\ProductStatus::Published,
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
}
