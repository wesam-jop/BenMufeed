<?php

namespace Tests\Feature\Auth;

use App\Models\Customer;
use App\Services\Shared\PhoneNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWhatsAppAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_service_defaults_to_syria(): void
    {
        $phones = app(PhoneNumberService::class);

        $this->assertSame('+963944567890', $phones->normalize('0944567890'));
        $this->assertTrue($phones->isValidMobile('+963944567890'));
    }

    public function test_customer_can_register_with_whatsapp_number(): void
    {
        $response = $this->post(route('customer.register'), [
            'name' => 'Wesam',
            'whatsapp' => '+963944567890',
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
        ]);

        $response->assertRedirect(route('customer.home', absolute: false));
        $this->assertAuthenticated('customer');

        $this->assertDatabaseHas('customers', [
            'phone' => '+963944567890',
            'whatsapp' => '+963944567890',
            'name' => 'Wesam',
        ]);
    }

    public function test_customer_register_rejects_invalid_whatsapp(): void
    {
        $this->post(route('customer.register'), [
            'whatsapp' => '123',
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
        ])->assertSessionHasErrors('whatsapp');
    }

    public function test_customer_can_login_with_whatsapp_e164(): void
    {
        Customer::query()->create([
            'phone' => '+963944567890',
            'whatsapp' => '+963944567890',
            'name' => 'Wesam',
            'password' => 'secret12',
            'is_active' => true,
        ]);

        $this->post(route('auth.login.store'), [
            'login' => '+963944567890',
            'password' => 'secret12',
        ])->assertRedirect(route('customer.home', absolute: false));

        $this->assertAuthenticated('customer');
    }

    public function test_customer_forgot_password_flow_in_local(): void
    {
        Customer::query()->create([
            'phone' => '+963944567890',
            'whatsapp' => '+963944567890',
            'password' => 'oldpass1',
            'is_active' => true,
        ]);

        $response = $this->post(route('customer.password.forgot.send'), [
            'whatsapp' => '+963944567890',
        ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertNotNull($location);
        $this->assertStringContainsString('/customer/password/reset/', $location);

        $token = basename(parse_url($location, PHP_URL_PATH));

        $this->post(route('customer.password.reset.store', ['token' => $token]), [
            'password' => 'newpass1',
            'password_confirmation' => 'newpass1',
        ])->assertRedirect(route('auth.login'));

        $this->post(route('auth.login.store'), [
            'login' => '0944567890',
            'password' => 'newpass1',
        ])->assertRedirect(route('customer.home', absolute: false));
    }
}
