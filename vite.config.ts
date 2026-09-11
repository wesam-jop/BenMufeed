import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

const root = path.dirname(fileURLToPath(import.meta.url));
const js = (...segments: string[]) => path.resolve(root, 'resources/js', ...segments);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.tsx'],
            refresh: true,
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
            { find: '@/components/admin', replacement: js('modules/admin/blocks') },
            { find: '@/components/pos', replacement: js('modules/pos/blocks') },
            { find: '@/components/kitchen', replacement: js('modules/kitchen/blocks') },
            { find: '@/components/storefront', replacement: js('modules/storefront/blocks') },
            { find: '@/components', replacement: js('shared/components') },
            { find: '@/layouts/admin-layout', replacement: js('modules/admin/layouts/admin-layout') },
            { find: '@/layouts/storefront-layout', replacement: js('modules/storefront/layouts/storefront-layout') },
            { find: '@/layouts/customer-layout', replacement: js('modules/customer/layouts/customer-layout') },
            { find: '@/layouts/auth-layout', replacement: js('modules/auth/layouts/auth-layout') },
            { find: '@/layouts/auth', replacement: js('modules/auth/layouts') },
            { find: '@/layouts/settings', replacement: js('modules/settings/layouts') },
            { find: '@/layouts/app-layout', replacement: js('shared/layouts/app-layout') },
            { find: '@/layouts/app', replacement: js('shared/layouts/app') },
            { find: '@/layouts', replacement: js('shared/layouts') },
            { find: '@/hooks', replacement: js('shared/hooks') },
            { find: '@/lib', replacement: js('shared/lib') },
            { find: '@/types', replacement: js('shared/types') },
            { find: '@/modules', replacement: js('modules') },
            { find: '@/shared', replacement: js('shared') },
            { find: '@', replacement: js() },
        ],
    },
    build: {
        cssCodeSplit: false,
        assetsInlineLimit: 100_000,
        chunkSizeWarningLimit: 5000,
        rollupOptions: {
            output: {
                entryFileNames: 'assets/app.js',
                chunkFileNames: 'assets/app.js',
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.names?.[0] ?? assetInfo.name ?? '';
                    if (name.endsWith('.css')) {
                        return 'assets/app.css';
                    }

                    return 'assets/[name][extname]';
                },
                codeSplitting: false,
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
