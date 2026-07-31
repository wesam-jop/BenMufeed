<?php

namespace App\Services;

use App\Models\StorefrontCustomer;
use App\Models\StorefrontCustomerAddress;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StorefrontCustomerAddressService
{
    public function listFor(StorefrontCustomer $customer): Collection
    {
        return $customer->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findForCustomer(StorefrontCustomer $customer, int $addressId): ?StorefrontCustomerAddress
    {
        return $customer->addresses()->whereKey($addressId)->first();
    }

    public function saveFromCheckout(
        StorefrontCustomer $customer,
        string $label,
        float $latitude,
        float $longitude,
        string $locationNote,
        bool $makeDefault = true,
    ): StorefrontCustomerAddress {
        $trimmedLabel = trim($label);
        $trimmedNote = trim($locationNote);

        if ($trimmedLabel === '') {
            throw ValidationException::withMessages([
                'address_label' => 'يرجى تسمية الموقع (مثال: البيت، العمل).',
            ]);
        }

        $max = (int) config('store.storefront_customer_max_addresses', 10);
        $count = $customer->addresses()->count();

        $existing = $customer->addresses()
            ->where('label', $trimmedLabel)
            ->first();

        if ($existing === null && $count >= $max) {
            throw ValidationException::withMessages([
                'address_label' => "يمكنك حفظ {$max} مواقع كحد أقصى.",
            ]);
        }

        if ($makeDefault) {
            $customer->addresses()->update(['is_default' => false]);
        }

        if ($existing !== null) {
            $existing->forceFill([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_note' => $trimmedNote,
                'is_default' => $makeDefault ? true : $existing->is_default,
            ])->save();

            return $existing->fresh();
        }

        return $customer->addresses()->create([
            'label' => $trimmedLabel,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_note' => $trimmedNote,
            'is_default' => $makeDefault || $count === 0,
        ]);
    }

    public function delete(StorefrontCustomer $customer, int $addressId): void
    {
        $address = $this->findForCustomer($customer, $addressId);

        if ($address === null) {
            throw ValidationException::withMessages([
                'address' => 'الموقع غير موجود.',
            ]);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $customer->addresses()->orderByDesc('updated_at')->first();

            if ($next !== null) {
                $next->forceFill(['is_default' => true])->save();
            }
        }
    }
}
