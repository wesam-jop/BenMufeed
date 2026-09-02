<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\PurchaseInvoiceStatus;
use App\Models\Branch;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\ChartOfAccountSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_invoice_receive_adds_stock_and_updates_cost(): void
    {
        [$user, $supplier, $warehouse, $product] = $this->seedPurchase();

        $invoice = PurchaseInvoice::query()->create([
            'branch_id' => Branch::query()->first()->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'PI-TEST-001',
            'status' => PurchaseInvoiceStatus::Draft,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->put(route('admin.purchase-invoices.update', $invoice), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_price' => 12.5,
                ],
            ],
            'tax' => 0,
        ])->assertRedirect(route('admin.purchase-invoices.show', $invoice));

        $response = $this->actingAs($user)->post(route('admin.purchase-invoices.receive', $invoice));

        $response->assertRedirect(route('admin.purchase-invoices.show', $invoice));

        $invoice->refresh();
        $product->refresh();

        $this->assertSame(PurchaseInvoiceStatus::Posted, $invoice->status);
        $this->assertSame(125.0, (float) $invoice->total);
        $this->assertSame(12.5, (float) $product->cost_price);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 10,
        ]);
    }

    public function test_draft_purchase_invoice_can_be_deleted(): void
    {
        [$user, $supplier, $warehouse] = $this->seedPurchase();

        $invoice = PurchaseInvoice::query()->create([
            'branch_id' => Branch::query()->first()->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'invoice_number' => 'PI-TEST-002',
            'status' => PurchaseInvoiceStatus::Draft,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.purchase-invoices.destroy', $invoice));

        $response->assertRedirect(route('admin.purchase-invoices.index'));
        $this->assertDatabaseMissing('purchase_invoices', ['id' => $invoice->id]);
    }

    /** @return array{0: User, 1: Supplier, 2: Warehouse, 3: Product} */
    private function seedPurchase(): array
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class, ChartOfAccountSeeder::class]);

        $branch = Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => 'password',
            'branch_id' => $branch->id,
            'is_super_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $warehouse = Warehouse::query()->create([
            'branch_id' => $branch->id,
            'name' => 'المستودع الرئيسي',
            'type' => 'main',
            'is_active' => true,
        ]);

        $supplier = Supplier::query()->create([
            'name' => 'مورد تجريبي',
            'is_active' => true,
        ]);

        $unit = Unit::query()->create([
            'name' => 'كيلو',
            'abbreviation' => 'kg',
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'name' => 'سكر',
            'sku' => 'RAW-SUGAR',
            'type' => ProductType::Raw,
            'track_stock' => true,
            'is_active' => true,
            'cost_price' => 5,
            'sale_price' => 0,
            'unit_id' => $unit->id,
        ]);

        return [$user, $supplier, $warehouse, $product];
    }
}
