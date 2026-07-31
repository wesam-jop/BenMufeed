import SiteLogo from '@/Components/Store/SiteLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="store-shell flex min-h-screen flex-col items-center px-4 pt-10 sm:justify-center sm:pt-0">
            <div className="mb-8 text-center animate-fade-in">
                <Link href="/" className="inline-block transition duration-fast hover:opacity-90">
                    <SiteLogo variant="compact" />
                </Link>
                <p className="mt-3 text-body text-store-muted">إدارة المطعم — بن مفيد</p>
            </div>

            <div className="store-card-raised w-full max-w-md px-6 py-8 animate-slide-up sm:px-8">
                {children}
            </div>

            <Link
                href="/"
                className="mt-8 text-body font-medium text-store-muted transition duration-fast hover:text-store-primary"
            >
                ← العودة للموقع
            </Link>
        </div>
    );
}
