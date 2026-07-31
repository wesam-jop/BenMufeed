import { Link } from '@inertiajs/react';
import SocialMeta from '@/Components/Store/SocialMeta';
import StoreLayout from '@/Layouts/StoreLayout';

export default function NotFound({ seo }) {
    return (
        <StoreLayout>
            <SocialMeta seo={seo} />

            <div className="mx-auto max-w-lg store-card-raised p-10 text-center animate-scale-in">
                <p className="font-display text-6xl font-bold text-store-secondary">404</p>
                <h1 className="font-display mt-4 text-h2 text-store-primary">الصفحة غير موجودة</h1>
                <p className="mt-3 text-body text-store-muted">
                    ربما أُزيل الرابط أو نُقل القسم. عد إلى قائمتنا واختر ما يشتهيه قلبك.
                </p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <Link href="/" className="store-btn-primary">
                        الرئيسية
                    </Link>
                    <Link href="/products" className="store-btn-outline">
                        القائمة
                    </Link>
                </div>
            </div>
        </StoreLayout>
    );
}
