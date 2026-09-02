<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_settings_page_loads(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('admin.site.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/site/index')
            ->has('settings')
            ->has('summary'));
    }

    public function test_site_settings_can_be_updated(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.site.update'), [
            'business_name' => 'مقهى كاشير',
            'tagline' => 'أفضل قهوة في المدينة',
            'hero_title' => 'مرحباً',
            'show_featured_products' => true,
            'contact_form_enabled' => false,
            'homepage_sections' => ['hero', 'featured'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $settings = SiteSetting::current();

        $this->assertSame('مقهى كاشير', $settings->business_name);
        $this->assertSame('أفضل قهوة في المدينة', $settings->tagline);
        $this->assertFalse($settings->contact_form_enabled);
        $this->assertSame(['hero', 'featured'], $settings->homepage_sections);
    }

    public function test_site_settings_can_upload_logo(): void
    {
        Storage::fake('public');
        $user = $this->adminUser();

        $response = $this->actingAs($user)->post(route('admin.site.update'), [
            'business_name' => 'Kasher Cafe',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $settings = SiteSetting::current()->fresh();
        $this->assertNotNull($settings->logo);
        Storage::disk('public')->assertExists($settings->logo);
    }

    public function test_site_messages_page_loads(): void
    {
        $user = $this->adminUser();

        ContactMessage::query()->create([
            'name' => 'زائر',
            'phone' => '0501234567',
            'message' => 'رسالة تجريبية',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.site.messages'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/site/messages')
            ->has('messages')
            ->has('summary')
            ->has('filters')
            ->where('summary.unread_count', 1));
    }

    public function test_contact_message_can_be_marked_read(): void
    {
        $user = $this->adminUser();

        $message = ContactMessage::query()->create([
            'name' => 'زائر',
            'message' => 'مرحباً',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->patch(route('admin.site.messages.read', $message));

        $response->assertRedirect();
        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_all_contact_messages_can_be_marked_read(): void
    {
        $user = $this->adminUser();

        ContactMessage::query()->create(['name' => 'أ', 'message' => '1', 'is_read' => false]);
        ContactMessage::query()->create(['name' => 'ب', 'message' => '2', 'is_read' => false]);

        $response = $this->actingAs($user)->patch(route('admin.site.messages.read-all'));

        $response->assertRedirect();
        $this->assertSame(0, ContactMessage::query()->where('is_read', false)->count());
    }

    public function test_contact_message_can_be_deleted(): void
    {
        $user = $this->adminUser();

        $message = ContactMessage::query()->create([
            'name' => 'زائر',
            'message' => 'حذف',
            'is_read' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('admin.site.messages.destroy', $message));

        $response->assertRedirect();
        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
    }

    public function test_contact_message_show_page_loads_and_marks_read(): void
    {
        $user = $this->adminUser();

        $message = ContactMessage::query()->create([
            'name' => 'زائر',
            'phone' => '0501234567',
            'message' => 'رسالة تفصيلية',
            'is_read' => false,
        ]);

        $response = $this->actingAs($user)->get(route('admin.site.messages.show', $message));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/site/show-message')
            ->where('message.id', $message->id)
            ->where('message.is_read', true));

        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_contact_message_can_be_marked_unread(): void
    {
        $user = $this->adminUser();

        $message = ContactMessage::query()->create([
            'name' => 'زائر',
            'message' => 'مرحباً',
            'is_read' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('admin.site.messages.unread', $message));

        $response->assertRedirect();
        $this->assertFalse($message->fresh()->is_read);
    }

    public function test_site_messages_can_filter_by_status(): void
    {
        $user = $this->adminUser();

        ContactMessage::query()->create(['name' => 'أ', 'message' => '1', 'is_read' => false]);
        ContactMessage::query()->create(['name' => 'ب', 'message' => '2', 'is_read' => true]);

        $response = $this->actingAs($user)->get(route('admin.site.messages', ['status' => 'unread']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('filters.status', 'unread')
            ->where('listSummary.count', 1)
            ->has('messages.data', 1));
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
