import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const browserHost = process.env.VITE_DEV_SERVER_HOST || 'localhost';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: `http://${browserHost}:5173`,
        hmr: { host: browserHost, clientPort: 5173 },
        cors: {
            origin: [
                process.env.APP_URL || 'http://localhost:8090',
                'http://127.0.0.1:8090',
            ],
        },
        watch: {
            usePolling: process.env.VITE_USE_POLLING === 'true',
            ignored: ['**/storage/framework/views/**'],
        },
    },
    assetsInclude: ['**/*.html'],
});
