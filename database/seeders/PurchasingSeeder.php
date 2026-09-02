<?php

namespace Database\Seeders;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use Database\Seeders\Concerns\InteractsWithMainBranch;
use Illuminate\Database\Seeder;

class PurchasingSeeder extends Seeder
{
    use InteractsWithMainBranch;

    public function run(): void
    {
        $branch = $this->mainBranch();
        $warehouse = $this->mainWarehouse();
        $user = $this->adminUser();
        $supplier = Supplier::query()->where('name', 'مورد القهوة المتخصص')->first();
        $coffee = Product::query()->where('sku', 'RAW-COFFEE')->first();

        if ($branch === null || $warehouse === null || $user === null || $supplier === null || $coffee === null) {
            return;
        }

        $invoice = PurchaseInvoice::query()->firstOrCreate(
            ['invoice_number' => 'PI-2026-001'],
            [
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'purchase_order_id' => null,
                'status' => PurchaseInvoiceStatus::Draft->value,
                'subtotal' => 2125,
                'tax' => 0,
                'total' => 2125,
                'paid' => 0,
                'balance' => 2125,
                'due_date' => now()->addDays(7)->toDateString(),
                'user_id' => $user->id,
            ],
        );

        PurchaseInvoiceItem::query()->firstOrCreate(
            ['purchase_invoice_id' => $invoice->id, 'product_id' => $coffee->id],
            ['quantity' => 25, 'unit_price' => 85, 'total' => 2125],
        );
    }
}
