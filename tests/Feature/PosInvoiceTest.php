<?php

namespace Tests\Feature;

use App\Actions\CompleteSaleAction;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use App\Enums\WarehouseType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_summary_shows_invoice_actions_page(): void
    {
        [$user, $invoice] = $this->completedSale();

        $response = $this->actingAs($user)->get(route('pos.sessions.daily-summary'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('pos/close-session')
            ->has('invoices', 1)
            ->where('invoices.0.id', $invoice->id));
    }

    public function test_invoice_can_be_voided_from_pos(): void
    {
        [$user, $invoice] = $this->completedSale();

        $response = $this->actingAs($user)->delete(route('pos.invoices.destroy', $invoice));

        $response->assertRedirect(route('pos.sessions.daily-summary'));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'void',
        ]);
    }

    public function test_invoice_print_page_loads(): void
    {
        [$user, $invoice] = $this->completedSale();

        $response = $this->actingAs($user)->get(route('pos.invoices.print', $invoice));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('pos/invoice-print')
            ->where('invoice.invoice_number', $invoice->invoice_number));
    }

    public function test_invoice_quantities_can_be_updated(): void
    {
        [$user, $invoice] = $this->completedSale();
        $product = Product::query()->firstOrFail();

        $response = $this->actingAs($user)->put(route('pos.invoices.update', $invoice), [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect(route('pos.sessions.daily-summary'));

        $invoice->refresh();

        $this->assertSame(2.0, (float) $invoice->items()->firstOrFail()->quantity);
        $this->assertSame(36.0, (float) $invoice->total);
    }

    public function test_invoice_can_add_new_product(): void
    {
        [$user, $invoice] = $this->completedSale();
        $existingProduct = Product::query()->firstOrFail();

        $newProduct = Product::query()->create([
            'branch_id' => $user->branch_id,
            'name' => 'كابتشينو',
            'type' => ProductType::Finished,
            'sale_price' => 15,
            'cost_price' => 5,
            'track_stock' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('pos.invoices.update', $invoice), [
            'items' => [
                ['product_id' => $existingProduct->id, 'quantity' => 1],
                ['product_id' => $newProduct->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect(route('pos.sessions.daily-summary'));

        $invoice->refresh();

        $this->assertCount(2, $invoice->items);
        $this->assertSame(33.0, (float) $invoice->total);
    }

    /**
     * @return array{0: User, 1: Invoice}
     */
    private function completedSale(): array
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class, ChartOfAccountSeeder::class]);

        $branch = Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
        ]);

        $user->assignRole('cashier');

        foreach (['sales.update', 'sales.cancel', 'sales.print_invoice'] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $user->givePermissionTo($permission);
        }

        $warehouse = Warehouse::query()->create([
            'branch_id' => $branch->id,
            'name' => 'المستودع الرئيسي',
            'type' => WarehouseType::Main,
            'is_active' => true,
        ]);

        CashRegister::query()->create([
            'branch_id' => $branch->id,
            'name' => 'الصندوق الرئيسي',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'name' => 'قهوة',
            'type' => ProductType::Finished,
            'sale_price' => 18,
            'cost_price' => 6,
            'track_stock' => false,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'ORD-INV-001',
            'type' => OrderType::Takeaway,
            'status' => OrderStatus::Confirmed,
            'user_id' => $user->id,
            'source' => 'pos',
            'subtotal' => 18,
            'tax_amount' => 0,
            'total' => 18,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 18,
            'cost_price' => 6,
            'tax' => 0,
            'status' => 'pending',
        ]);

        $register = app(\App\Services\Accounting\CashRegisterService::class);
        $registerModel = \App\Models\CashRegister::query()->first();
        $session = $register->getOrCreateDailySession($registerModel, $user);

        $invoice = app(CompleteSaleAction::class)->execute(
            order: $order->fresh(['items.product']),
            warehouse: $warehouse,
            payments: [['method' => 'cash', 'amount' => 18]],
            session: $session,
            user: $user,
        );

        return [$user, $invoice];
    }
}
