import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        ...(process.env.VITE_DEV_SERVER_HOST ? {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
            hmr: { host: process.env.VITE_DEV_SERVER_HOST },
        } : {}),
        watch: {
            usePolling: process.env.VITE_USE_POLLING === 'true',
            ignored: ['**/storage/framework/views/**'],
        },
    },
    assetsInclude: ['**/*.html'],
});
