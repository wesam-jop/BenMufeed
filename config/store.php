<?php

return [

    'currency_code' => env('STORE_CURRENCY', 'USD'),

    'currency_symbol' => '$',

    'currency_notice' => 'جميع أسعار قائمة المطعم تُدخل وتُعرض بالدولار الأمريكي ($).',

    'products_per_page' => (int) env('STORE_PRODUCTS_PER_PAGE', 15),

    'categories_per_page' => (int) env('STORE_CATEGORIES_PER_PAGE', 15),

    /*
    |--------------------------------------------------------------------------
    | تخزين الصور في المتصفح (ثواني) — سنة واحدة
    |--------------------------------------------------------------------------
    */
    'media_cache_max_age' => (int) env('STORE_MEDIA_CACHE_MAX_AGE', 31536000),

    /*
    |--------------------------------------------------------------------------
    | جلسة مسح QR — بدون نشاط أو بعد مغادرة المقهى يجب إعادة المسح
    |--------------------------------------------------------------------------
    */
    // مهلة الخمول: 0 = معطّلة (التوكن صالح لمدة ثابتة من لحظة المسح فقط).
    'table_scan_idle_minutes' => (int) env('STORE_TABLE_SCAN_IDLE_MINUTES', 0),
    'table_scan_max_hours' => (int) env('STORE_TABLE_SCAN_MAX_HOURS', 4),

    // كوكي جلسة مسح الطاولة (httpOnly) — يمنع الدخول المباشر بالرابط.
    'table_scan_cookie' => env('STORE_TABLE_SCAN_COOKIE', 'bm_ts'),

    // كوكي رمز الطلب المفتوح للطاولة (يقرأه العميل لإضافة أصناف).
    'table_order_cookie' => env('STORE_TABLE_ORDER_COOKIE', 'bm_tos'),

    /*
    |--------------------------------------------------------------------------
    | حساب زبون الموقع (StorefrontCustomer)
    |--------------------------------------------------------------------------
    */
    'storefront_customer_cookie' => env('STOREFRONT_CUSTOMER_COOKIE', 'bm_sc'),
    'storefront_customer_session_days' => (int) env('STOREFRONT_CUSTOMER_SESSION_DAYS', 30),
    'storefront_customer_otp_ttl_minutes' => (int) env('STOREFRONT_CUSTOMER_OTP_TTL_MINUTES', 10),
    'storefront_customer_otp_resend_seconds' => (int) env('STOREFRONT_CUSTOMER_OTP_RESEND_SECONDS', 60),
    'storefront_customer_otp_max_attempts' => (int) env('STOREFRONT_CUSTOMER_OTP_MAX_ATTEMPTS', 3),
    'storefront_customer_max_addresses' => (int) env('STOREFRONT_CUSTOMER_MAX_ADDRESSES', 10),

];
