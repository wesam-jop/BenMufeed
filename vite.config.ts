import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

const root = path.dirname(fileURLToPath(import.meta.url));
const js = (...segments: string[]) => path.resolve(root, 'resources/js', ...segments);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Cairo', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    resolve: {
        alias: [
            // Area blocks (must be before generic @/components)
            { find: '@/components/admin', replacement: js('modules/admin/blocks') },
            { find: '@/components/pos', replacement: js('modules/pos/blocks') },
            { find: '@/components/kitchen', replacement: js('modules/kitchen/blocks') },
            { find: '@/components/storefront', replacement: js('modules/storefront/blocks') },
            // Shared components (ui, domain, receipt, shell)
            { find: '@/components', replacement: js('shared/components') },
            // Layouts
            { find: '@/layouts/admin-layout', replacement: js('modules/admin/layouts/admin-layout') },
            { find: '@/layouts/storefront-layout', replacement: js('modules/storefront/layouts/storefront-layout') },
            { find: '@/layouts/customer-layout', replacement: js('modules/customer/layouts/customer-layout') },
            { find: '@/layouts/auth-layout', replacement: js('modules/auth/layouts/auth-layout') },
            { find: '@/layouts/auth', replacement: js('modules/auth/layouts') },
            { find: '@/layouts/settings', replacement: js('modules/settings/layouts') },
            { find: '@/layouts/app-layout', replacement: js('shared/layouts/app-layout') },
            { find: '@/layouts/app', replacement: js('shared/layouts/app') },
            { find: '@/layouts', replacement: js('shared/layouts') },
            // Shared libs
            { find: '@/hooks', replacement: js('shared/hooks') },
            { find: '@/lib', replacement: js('shared/lib') },
            { find: '@/types', replacement: js('shared/types') },
            // Modules / shared roots for new imports
            { find: '@/modules', replacement: js('modules') },
            { find: '@/shared', replacement: js('shared') },
            { find: '@', replacement: js() },
        ],
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    const normalized = id.replace(/\\/g, '/');
                    if (!normalized.includes('/resources/js/')) {
                        return;
                    }
                    if (normalized.includes('/resources/js/modules/admin/')) {
                        return 'admin';
                    }
                    if (normalized.includes('/resources/js/modules/pos/')) {
                        return 'pos';
                    }
                    if (normalized.includes('/resources/js/modules/kitchen/')) {
                        return 'kitchen';
                    }
                    if (normalized.includes('/resources/js/modules/customer/')) {
                        return 'customer';
                    }
                    if (normalized.includes('/resources/js/modules/storefront/')) {
                        return 'storefront';
                    }
                    if (
                        normalized.includes('/resources/js/modules/auth/') ||
                        normalized.includes('/resources/js/modules/settings/')
                    ) {
                        return 'auth-settings';
                    }
                    if (normalized.includes('/resources/js/shared/')) {
                        return 'shared';
                    }
                },
            },
        },
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
});
