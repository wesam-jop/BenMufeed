<?php

namespace App\Services\Storefront;

use App\Models\Customer;
use App\Services\Sales\OrderOtpService;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Session\Session;

class GuestProfileService
{
    public const SESSION_KEY = 'storefront_guest_profile';

    public function __construct(
        protected Session $session,
        protected AuthManager $auth,
    ) {}

    /**
     * @return array{name: string, whatsapp: string, phone: string}|null
     */
    public function get(): ?array
    {
        $customer = $this->auth->guard('customer')->user();

        if ($customer instanceof Customer) {
            return $this->fromCustomer($customer);
        }

        $profile = $this->session->get(self::SESSION_KEY);

        if (! is_array($profile) || empty($profile['name']) || empty($profile['phone'])) {
            return null;
        }

        return [
            'name' => $profile['name'],
            'whatsapp' => $profile['whatsapp'] ?? $this->displayPhone($profile['phone']),
            'phone' => $profile['phone'],
        ];
    }

    public function has(): bool
    {
        return $this->get() !== null;
    }

    public function save(string $name, string $normalizedPhone): void
    {
        $customer = $this->auth->guard('customer')->user();

        if ($customer instanceof Customer) {
            $customer->update([
                'name' => $name,
                'whatsapp' => $normalizedPhone,
            ]);

            return;
        }

        $this->session->put(self::SESSION_KEY, [
            'name' => $name,
            'phone' => $normalizedPhone,
            'whatsapp' => $this->displayPhone($normalizedPhone),
        ]);
    }

    public function clearSession(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * @return array{name: string, whatsapp: string, phone: string}
     */
    public function fromCustomer(Customer $customer): array
    {
        $normalizedPhone = OrderOtpService::normalizePhone($customer->phone);

        return [
            'name' => $customer->name ?? '',
            'whatsapp' => $customer->phone,
            'phone' => $normalizedPhone,
        ];
    }

    public function displayPhone(string $normalizedPhone): string
    {
        if (str_starts_with($normalizedPhone, '966')) {
            return '0'.substr($normalizedPhone, 3);
        }

        return $normalizedPhone;
    }
}
