import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Panel Blade actual: sigue funcionando durante la migración.
                'resources/css/app.css',
                'resources/js/app.js',
                // SPA de React.
                'resources/css/spa.css',
                'resources/js/spa/main.jsx',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
