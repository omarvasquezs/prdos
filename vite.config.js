import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    server: {
        hmr: {
            host: process.env.DDEV_HOSTNAME,
            protocol: 'wss'
        }
    },
    plugins: [
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js', 'resources/js/login.js'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true, // Silencia advertencias de dependencias (Bootstrap)
            },
        },
    },
});
