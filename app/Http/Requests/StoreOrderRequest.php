<?php

namespace App\Http\Requests;

use App\Enums\PosServiceType;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\PosService;
use App\Services\RestaurantTableService;
use App\Services\StorefrontCustomerAddressService;
use App\Services\StorefrontCustomerAuthService;
use App\Support\PhoneCountries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $serviceType = $this->input('service_type');
        $isPickup = $serviceType === PosServiceType::Takeaway->value;
        $isDelivery = $serviceType === PosServiceType::Delivery->value;
        $isTable = $serviceType === PosServiceType::Table->value;
        $needsCustomer = $isPickup || $isDelivery;

        return [
            'service_type' => ['required', Rule::enum(PosServiceType::class)],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', ProductStatus::Published->value),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'items.*.addons' => ['nullable', 'array', 'max:20'],
            'items.*.addons.*.product_addon_id' => ['required', 'integer', 'exists:product_addons,id'],
            'items.*.addons.*.quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'table_token' => [
                Rule::requiredIf($isTable),
                'nullable',
                'string',
                'max:64',
                Rule::exists('restaurant_tables', 'qr_token')->where('is_active', true),
            ],
            'table_session_token' => ['nullable', 'string', 'max:64'],
            'table_scan_session' => [
                Rule::requiredIf($isTable),
                'nullable',
                'string',
                'max:64',
            ],
            'customer_name' => [
                Rule::requiredIf($needsCustomer),
                'nullable',
                'string',
                'max:120',
            ],
            'customer_phone' => [
                Rule::requiredIf($needsCustomer),
                'nullable',
                'string',
                'max:32',
            ],
            'customer_phone_country' => ['nullable', 'string', 'max:16'],
            'customer_phone_local' => ['nullable', 'string', 'max:16'],
            'delivery_latitude' => [
                Rule::requiredIf($isDelivery && ! $this->filled('saved_address_id')),
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'delivery_longitude' => [
                Rule::requiredIf($isDelivery && ! $this->filled('saved_address_id')),
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'delivery_location_note' => [
                Rule::requiredIf($isDelivery && ! $this->filled('saved_address_id')),
                'nullable',
                'string',
                'max:500',
            ],
            'saved_address_id' => ['nullable', 'integer', 'exists:storefront_customer_addresses,id'],
            'save_address' => ['nullable', 'boolean'],
            'address_label' => [
                Rule::requiredIf($isDelivery && $this->boolean('save_address')),
                'nullable',
                'string',
                'max:80',
            ],
            'customer_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'طلبك فارغ — أضف أطباقاً من القائمة.',
            'items.min' => 'طلبك فارغ — أضف أطباقاً من القائمة.',
            'items.max' => 'عدد الأطباق في الطلب كبير جداً.',
            'service_type.required' => 'اختر نوع الطلب.',
            'customer_name.required' => 'يرجى إدخال الاسم.',
            'customer_phone.required' => 'يرجى إدخال رقم الهاتف.',
            'delivery_latitude.required' => 'حدّد موقع التوصيل على الخريطة.',
            'delivery_longitude.required' => 'حدّد موقع التوصيل على الخريطة.',
            'delivery_location_note.required' => 'أضف وصفاً لموقع التوصيل.',
            'table_token.required' => 'امسح رمز QR على الطاولة.',
            'table_scan_session.required' => 'امسح رمز QR على الطاولة — الجلسة غير نشطة.',
            'table_token.exists' => 'رمز الطاولة غير صالح.',
            'items.*.product_id.exists' => 'أحد الأطباق غير متوفر حالياً.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $serviceType = $this->input('service_type');

        if (! in_array($serviceType, [PosServiceType::Takeaway->value, PosServiceType::Delivery->value], true)) {
            return;
        }

        $country = (string) $this->input('customer_phone_country', '');
        $local = (string) $this->input('customer_phone_local', '');

        if ($country !== '' && $local !== '') {
            [$dial, $iso] = PhoneCountries::splitOptionKey($country);
            $this->merge([
                'customer_phone' => PhoneCountries::format($dial, $iso, $local),
            ]);

            return;
        }

        $existing = (string) $this->input('customer_phone', '');

        if ($existing !== '') {
            $parsed = PhoneCountries::parse($existing);
            $this->merge([
                'customer_phone' => PhoneCountries::format($parsed['dial'], $parsed['iso'], $parsed['local']),
            ]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $serviceType = $this->input('service_type');

            if (in_array($serviceType, [PosServiceType::Takeaway->value, PosServiceType::Delivery->value], true)) {
                $customer = app(StorefrontCustomerAuthService::class)->resolve($this);

                if ($customer === null || ! $customer->isVerified()) {
                    $validator->errors()->add(
                        'otp',
                        'يرجى التحقق من رقم الواتساب قبل تأكيد الطلب.',
                    );

                    return;
                }

                $phone = (string) $customer->phone;

                if ($phone === '' || ! PhoneCountries::isValidFormatted($phone)) {
                    $validator->errors()->add(
                        'customer_phone_local',
                        'يرجى إدخال رقم جوال صالح.',
                    );
                }

                if ($serviceType === PosServiceType::Delivery->value) {
                    $productIds = collect($this->input('items', []))
                        ->pluck('product_id')
                        ->map(fn ($id) => (int) $id)
                        ->filter(fn (int $id) => $id > 0)
                        ->unique()
                        ->values();

                    if ($productIds->isNotEmpty()) {
                        $blocked = Product::query()
                            ->published()
                            ->whereIn('id', $productIds->all())
                            ->where('is_delivery_available', false)
                            ->pluck('name')
                            ->values();

                        if ($blocked->isNotEmpty()) {
                            $validator->errors()->add(
                                'items',
                                'هذه الأصناف غير متاحة للتوصيل: '.$blocked->join('، '),
                            );
                        }
                    }

                    $savedAddressId = $this->input('saved_address_id');

                    if (filled($savedAddressId)) {
                        $saved = app(StorefrontCustomerAddressService::class)
                            ->findForCustomer($customer, (int) $savedAddressId);

                        if ($saved === null) {
                            $validator->errors()->add(
                                'saved_address_id',
                                'الموقع المحفوظ غير صالح.',
                            );
                        }
                    } elseif (
                        ! is_numeric($this->input('delivery_latitude'))
                        || ! is_numeric($this->input('delivery_longitude'))
                        || trim((string) $this->input('delivery_location_note', '')) === ''
                    ) {
                        $validator->errors()->add(
                            'delivery_latitude',
                            'حدّد موقع التوصيل أو اختر موقعاً محفوظاً.',
                        );
                    }
                }
            }

            if ($serviceType !== PosServiceType::Table->value) {
                return;
            }

            $table = app(RestaurantTableService::class)->findByToken((string) $this->input('table_token'));

            if ($table === null) {
                return;
            }

            $scanToken = (string) $this->input('table_scan_session', '');

            if ($scanToken !== '') {
                try {
                    app(\App\Services\TableScanSessionService::class)->validateAndTouch($scanToken, $table);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $validator->errors()->add(
                        'table_scan_session',
                        collect($exception->errors())->flatten()->first() ?? 'انتهت جلسة الطاولة.',
                    );
                }
            }

            $openOrder = app(PosService::class)->findOpenTableOrder($table->number);

            if ($openOrder === null) {
                return;
            }

            $sessionToken = (string) $this->input('table_session_token', '');

            if (
                filled($openOrder->customer_session_token)
                && filled($sessionToken)
                && hash_equals((string) $openOrder->customer_session_token, $sessionToken)
            ) {
                return;
            }

            $validator->errors()->add(
                'table_token',
                'الطاولة مفتوحة — لا يمكن إضافة طلب جديد عليها.',
            );
        });
    }
}
