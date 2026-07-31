import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard() {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="font-display text-h3 text-store-primary">
                    لوحة التحكم
                </h2>
            }
        >
            <Head title="لوحة التحكم" />

            <div className="admin-card p-6 sm:p-8">
                <p className="text-body-lg text-store-primary">
                    أهلاً بك. يمكنك إدارة القائمة والطلبات من{' '}
                    <a
                        href="/admin"
                        className="font-semibold text-store-secondary underline-offset-2 transition duration-fast hover:underline"
                    >
                        لوحة بن مفيد Ben Mufeed
                    </a>
                    .
                </p>
                <div className="mt-6 flex flex-wrap gap-3">
                    <a href="/admin" className="store-btn-primary">
                        فتح لوحة الإدارة
                    </a>
                    <Link href="/" className="store-btn-outline">
                        زيارة الموقع
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
