import path from 'node:path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.jsx',
                'resources/js/desk.js',
                'resources/js/pos.js',
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': path.resolve('resources/js'),
        },
    },
    build: {
        cssCodeSplit: false,
        chunkSizeWarningLimit: 2000,
    },
});
