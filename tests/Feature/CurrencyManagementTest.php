<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Currency;
use App\Models\User;
use App\Services\Shared\CurrencyService;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_currencies_page_loads(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get(route('admin.settings.currencies'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/settings/currencies')
                ->has('currencies')
                ->has('primary'));
    }

    public function test_can_add_secondary_currency(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)
            ->post(route('admin.settings.currencies.store'), [
                'code' => 'GBP',
                'name' => 'جنيه إسترليني',
                'symbol' => '£',
                'symbol_position' => 'before',
                'decimal_places' => 2,
                'rate_to_primary' => 1.27,
                'show_on_receipt' => true,
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('currencies', [
            'code' => 'GBP',
            'show_on_receipt' => 1,
        ]);
    }

    public function test_receipt_conversion_uses_rate_to_primary(): void
    {
        $this->adminUser();

        $service = app(CurrencyService::class);
        $rows = $service->convertForReceipt(100.0);

        $try = collect($rows)->firstWhere('code', 'TRY');
        $this->assertNotNull($try);
        $this->assertEqualsWithDelta(3450.0, $try['amount'], 0.01);

        $syp = collect($rows)->firstWhere('code', 'SYP');
        $this->assertNotNull($syp);
        $this->assertEquals(1250000, $syp['amount']);
    }

    public function test_can_switch_primary_currency(): void
    {
        $user = $this->adminUser();
        $try = Currency::query()->where('code', 'TRY')->firstOrFail();

        $this->actingAs($user)
            ->post(route('admin.settings.currencies.make-primary', $try))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $try->fresh()->is_primary);
        $this->assertFalse((bool) Currency::query()->where('code', 'USD')->firstOrFail()->is_primary);
        $this->assertSame('TRY', app(CurrencyService::class)->primary()->code);
    }

    private function adminUser(): User
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class, CurrencySeeder::class]);

        Branch::query()->create([
            'name' => 'الفرع الرئيسي',
            'code' => 'MAIN',
            'is_active' => true,
        ]);

        return User::query()->create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@test.local',
            'password' => 'password',
            'is_super_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
