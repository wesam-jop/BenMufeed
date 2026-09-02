<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_settings_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.settings.business'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/settings/business')
            ->has('settings')
            ->has('summary')
            ->has('options'));
    }

    public function test_business_settings_can_be_updated(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->put(route('admin.settings.business.update'), [
            'business_name' => 'مقهى كاشير',
            'description' => 'أفضل قهوة',
            'opening_hours' => '8:00 – 11:00',
            'phone' => '0501234567',
            'email' => 'info@kasher.test',
            'address' => 'الرياض',
            'whatsapp' => '0501234567',
            'instagram' => '@kasher',
            'tax_number' => '300123456789003',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
            'receipt_footer' => 'شكراً لزيارتكم',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $settings = Cache::get('business_settings');

        $this->assertSame('مقهى كاشير', $settings['business_name']);
        $this->assertSame('SAR', $settings['currency']);
        $this->assertSame('Asia/Riyadh', $settings['timezone']);
        $this->assertSame('شكراً لزيارتكم', $settings['receipt_footer']);
        $this->assertNotNull($settings['updated_at'] ?? null);
    }

    public function test_business_settings_prefill_from_site_and_branch_defaults(): void
    {
        $user = $this->adminUser();

        Branch::query()->first()?->update([
            'name' => 'فرع الرياض',
            'phone' => '0112223333',
            'currency' => 'SAR',
            'timezone' => 'Asia/Riyadh',
        ]);

        $response = $this->actingAs($user)->get(route('admin.settings.business'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('settings.business_name', 'Kasher Cafe')
            ->where('settings.currency', 'SAR'));
    }

    private function adminUser(): User
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

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
