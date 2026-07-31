<?php

namespace App\Services;

use App\Enums\StorefrontCustomerAccountType;
use App\Enums\StorefrontCustomerCreatedVia;
use App\Models\RestaurantTable;
use App\Models\StorefrontCustomer;

class StorefrontCustomerBootstrapService
{
    public function __construct(
        protected StorefrontCustomerAuthService $auth,
    ) {}

    /**
     * @return array{customer: StorefrontCustomer, token: string, created: bool}
     */
    public function bootstrapTableGuest(RestaurantTable $table, ?StorefrontCustomer $existing = null): array
    {
        if ($existing !== null && $existing->is_active) {
            $updates = [
                'default_restaurant_table_id' => $table->getKey(),
            ];

            if ($existing->isTableGuest()) {
                $updates['name'] = $this->defaultTableName($table);
            }

            $existing->forceFill($updates)->save();

            return [
                'customer' => $existing->fresh('defaultTable'),
                'token' => $this->auth->issueSession($existing),
                'created' => false,
            ];
        }

        $customer = StorefrontCustomer::query()->create([
            'name' => $this->defaultTableName($table),
            'phone' => null,
            'phone_verified_at' => null,
            'account_type' => StorefrontCustomerAccountType::TableGuest,
            'default_restaurant_table_id' => $table->getKey(),
            'created_via' => StorefrontCustomerCreatedVia::TableQr,
            'is_active' => true,
        ]);

        return [
            'customer' => $customer->load('defaultTable'),
            'token' => $this->auth->issueSession($customer),
            'created' => true,
        ];
    }

    protected function defaultTableName(RestaurantTable $table): string
    {
        return 'زبون — '.$table->displayName();
    }
}
