# Kasher — Cafe ERP / POS

نظام متكامل لإدارة المقاهي: POS، مخزون، مشتريات، محاسبة، طاولات، QR، بوابة عملاء.

## Stack

- Laravel 13 + PHP 8.3
- React 19 + TypeScript + Inertia v3
- Tailwind CSS v4 + shadcn/ui
- SQLite (dev) / MySQL (production)
- Spatie Laravel Permission

## التشغيل

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
composer run dev
```

## بيانات الدخول الافتراضية

| الحقل | القيمة |
|-------|--------|
| Username | `admin` |
| Password | `password` |

## الوحدات

- **لوحة الإدارة** `/admin` — Dashboard، منتجات، مخزون، مشتريات، مبيعات، محاسبة، تقارير، مستخدمين
- **POS** `/pos` — نقطة البيع
- **المطبخ** `/kitchen` — شاشة تحضير الطلبات
- **بوابة العميل** `/customer` — طلبات، مفضلة، عناوين
- **QR** `/order/{token}` — طلب من الطاولة

## الصلاحيات

87 صلاحية ديناميكية عبر Spatie. Super Admin (`is_super_admin`) يتجاوز كل القيود.

## Branch

النظام جاهز multi-branch. الفرع الحالي يُخزَّن في session `branch_id`.
