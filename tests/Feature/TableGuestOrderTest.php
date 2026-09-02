<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\ProductType;
use App\Enums\TableStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RestaurantTable;
use App\Models\SiteSetting;
use App\Models\TableSession;
use App\Models\User;
use App\Services\Sales\TableGuestSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TableGuestOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_activate_page_loads(): void
    {
        $this->get(route('table-order.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('customer/table-order/activate')
                ->has('tables'));
    }

    public function test_wrong_access_code_is_rejected(): void
    {
        [, $table] = $this->guestContext();

        $this->from(route('table-order.index'))
            ->post(route('table-order.activate'), [
                'table_id' => $table->id,
                'access_code' => '000000',
            ])
            ->assertSessionHasErrors('access_code');
    }

    public function test_guest_can_activate_with_encrypted_qr_token(): void
    {
        [, $table] = $this->guestContext();

        $token = SiteSetting::tableAccessToken();

        $this->post(route('table-order.activate'), [
            'table_id' => $table->id,
            'access_token' => $token,
        ])->assertRedirect(route('table-order.menu'));

        $this->assertDatabaseHas('table_sessions', [
            'table_id' => $table->id,
            'status' => 'open',
        ]);
    }

    public function test_tampered_qr_token_is_rejected(): void
    {
        [, $table] = $this->guestContext();

        $this->from(route('table-order.index'))
            ->post(route('table-order.activate'), [
                'table_id' => $table->id,
                'access_token' => 'not-a-valid-token',
            ])
            ->assertSessionHasErrors(['access_code', 'access_token']);
    }

    public function test_cannot_activate_already_open_table(): void
    {
        [, $table] = $this->guestContext();

        TableSession::query()->create([
            'table_id' => $table->id,
            'session_token' => 'existing-token',
            'status' => 'open',
            'opened_at' => now(),
            'expires_at' => now()->addHour(),
            'activated_via' => 'qr_unified',
        ]);
        $table->update(['status' => TableStatus::Occupied]);

        $this->from(route('table-order.index'))
            ->post(route('table-order.activate'), [
                'table_id' => $table->id,
                'access_code' => SiteSetting::tableAccessCode(),
            ])
            ->assertSessionHasErrors('table_id');

        $this->assertSame(1, TableSession::query()->where('table_id', $table->id)->where('status', 'open')->count());
    }

    public function test_activate_page_marks_open_tables_unavailable(): void
    {
        [, $table] = $this->guestContext();

        TableSession::query()->create([
            'table_id' => $table->id,
            'session_token' => 'existing-token',
            'status' => 'open',
            'opened_at' => now(),
            'expires_at' => now()->addHour(),
            'activated_via' => 'qr_unified',
        ]);
        $table->update(['status' => TableStatus::Occupied]);

        $this->get(route('table-order.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('customer/table-order/activate')
                ->where('tables.0.is_available', false));
    }

    public function test_browsing_session_keeps_table_available(): void
    {
        [, $table] = $this->guestContext();

        TableSession::query()->create([
            'table_id' => $table->id,
            'session_token' => 'browse-only',
            'status' => 'open',
            'opened_at' => now(),
            'expires_at' => now()->addHour(),
            'activated_via' => 'qr_unified',
        ]);
        $table->update(['status' => TableStatus::Free]);

        $this->get(route('table-order.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('customer/table-order/activate')
                ->where('tables.0.is_available', true));
    }

    public function test_guest_can_activate_session_and_order(): void
    {
        [, $table, $product] = $this->guestContext();

        $response = $this->post(route('table-order.activate'), [
            'table_id' => $table->id,
            'access_code' => SiteSetting::tableAccessCode(),
            'guest_count' => 2,
        ]);

        $response->assertRedirect(route('table-order.menu'));

        $session = TableSession::query()->where('table_id', $table->id)->first();
        $this->assertNotNull($session);
        $this->assertTrue($session->isActive());
        $this->assertSame('qr_unified', $session->activated_via);
        $this->assertSame(TableStatus::Free, $table->fresh()->status);

        $this->withCookie(TableGuestSessionService::COOKIE_NAME, $session->session_token)
            ->post(route('table-order.orders.store'), [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'table_session_id' => $session->id,
            'source' => 'qr',
            'type' => OrderType::DineIn->value,
            'status' => OrderStatus::Pending->value,
        ]);

        $this->assertSame(TableStatus::Free, $table->fresh()->status);
    }

    public function test_expired_session_cannot_order(): void
    {
        [, $table, $product] = $this->guestContext();

        $session = TableSession::query()->create([
            'table_id' => $table->id,
            'session_token' => 'expired-token-abc',
            'status' => 'open',
            'opened_at' => now()->subHours(2),
            'expires_at' => now()->subMinute(),
            'activated_via' => 'qr_unified',
        ]);

        $this->withCookie(TableGuestSessionService::COOKIE_NAME, $session->session_token)
            ->post(route('table-order.orders.store'), [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])
            ->assertRedirect(route('table-order.index'));

        $this->assertSame(0, Order::query()->count());
    }

    public function test_cashier_can_accept_table_order_for_kitchen(): void
    {
        [$branch, $table] = $this->guestContext();

        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);

        foreach (['pos.access', 'orders.accept_table', 'orders.reject_table', 'kitchen.view'] as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        $session = TableSession::query()->create([
            'table_id' => $table->id,
            'session_token' => 'tok-accept',
            'status' => 'open',
            'opened_at' => now(),
            'expires_at' => now()->addHour(),
            'activated_via' => 'qr_unified',
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'ORD-QR-2',
            'type' => OrderType::DineIn,
            'status' => OrderStatus::Pending,
            'table_session_id' => $session->id,
            'source' => 'qr',
            'subtotal' => 15,
            'total' => 15,
        ]);

        $this->actingAs($user)
            ->get(route('kitchen.display', ['status' => 'confirmed']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('orders', 0));

        $this->actingAs($user)
            ->postJson(route('pos.table-orders.accept', $order))
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Confirmed->value,
        ]);

        $this->assertSame(TableStatus::Occupied, $table->fresh()->status);

        $this->actingAs($user)
            ->get(route('kitchen.display', ['status' => 'confirmed']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('orders', 1)
                ->where('orders.0.order_number', 'ORD-QR-2'));
    }

    public function test_cashier_can_reject_table_order(): void
    {
        [$branch, $table] = $this->guestContext();

        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);

        foreach (['orders.reject_table'] as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }

        $session = TableSession::query()->create([
            'table_id' => $table->id,
            'session_token' => 'tok-reject',
            'status' => 'open',
            'opened_at' => now(),
            'expires_at' => now()->addHour(),
            'activated_via' => 'qr_unified',
        ]);

        $order = Order::query()->create([
            'branch_id' => $branch->id,
            'order_number' => 'ORD-QR-3',
            'type' => OrderType::DineIn,
            'status' => OrderStatus::Pending,
            'table_session_id' => $session->id,
            'source' => 'qr',
            'subtotal' => 10,
            'total' => 10,
        ]);

        $this->actingAs($user)
            ->postJson(route('pos.table-orders.reject', $order), ['reason' => 'نفد الصنف'])
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::Rejected->value,
            'rejection_reason' => 'نفد الصنف',
        ]);
    }

    /**
     * @return array{0: Branch, 1: RestaurantTable, 2: Product}
     */
    private function guestContext(): array
    {
        $branch = Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        $table = RestaurantTable::query()->create([
            'branch_id' => $branch->id,
            'name' => 'طاولة 1',
            'capacity' => 4,
            'status' => TableStatus::Free,
            'sort_order' => 1,
        ]);

        SiteSetting::current()->forceFill([
            'table_access_code' => '123456',
        ])->save();

        $category = ProductCategory::query()->create([
            'branch_id' => $branch->id,
            'name' => 'مشروبات',
            'slug' => 'drinks-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::query()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'name' => 'قهوة',
            'sku' => 'COF-'.uniqid(),
            'type' => ProductType::Finished,
            'sale_price' => 15,
            'cost_price' => 5,
            'is_active' => true,
        ]);

        return [$branch, $table, $product];
    }
}
