<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\WastageReason;
use App\Models\Branch;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WastageRecord;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WastageRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_wastage_record_deducts_stock_on_create(): void
    {
        [$user, $warehouse, $product] = $this->seedInventory();

        StockBalance::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'avg_cost' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('admin.wastage-records.store'), [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'reason' => WastageReason::Damaged->value,
            'notes' => 'تلف أثناء النقل',
        ]);

        $record = WastageRecord::query()->first();

        $response->assertRedirect(route('admin.wastage-records.show', $record));

        $this->assertDatabaseHas('wastage_records', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'reason' => WastageReason::Damaged->value,
        ]);

        $this->assertNotNull($record?->stock_movement_id);

        $this->assertDatabaseHas('stock_movements', [
            'id' => $record?->stock_movement_id,
            'type' => 'wastage',
            'quantity' => 3,
        ]);

        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 7,
        ]);
    }

    public function test_wastage_record_rejects_quantity_above_available(): void
    {
        [$user, $warehouse, $product] = $this->seedInventory();

        StockBalance::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'avg_cost' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('admin.wastage-records.store'), [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'reason' => WastageReason::Spillage->value,
        ]);

        $response->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('wastage_records', 0);
    }

    public function test_wastage_record_delete_restores_stock(): void
    {
        [$user, $warehouse, $product] = $this->seedInventory();

        StockBalance::query()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'avg_cost' => 5,
        ]);

        $this->actingAs($user)->post(route('admin.wastage-records.store'), [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'reason' => WastageReason::Expired->value,
        ]);

        $record = WastageRecord::query()->firstOrFail();

        $response = $this->actingAs($user)->delete(route('admin.wastage-records.destroy', $record));

        $response->assertRedirect(route('admin.wastage-records.index'));
        $this->assertDatabaseMissing('wastage_records', ['id' => $record->id]);
        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
    }

    /** @return array{0: User, 1: Warehouse, 2: Product} */
    private function seedInventory(): array
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

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
            'cost_price' => 10,
            'sale_price' => 0,
            'unit_id' => $unit->id,
        ]);

        return [$user, $warehouse, $product];
    }
}
