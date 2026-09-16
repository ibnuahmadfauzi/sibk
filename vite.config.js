import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/scss/app-auth.scss',
                'resources/scss/app-dashboard.scss',
                'resources/js/app-auth.js',
                'resources/js/app-dashboard.js',
            ],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Sembunyikan deprecation warnings dari Bootstrap 5 yang spam di terminal
                quietDeps: true,
                silenceDeprecations: ['legacy-js-api', 'import', 'global-builtin', 'color-functions', 'if-function']
            }
        }
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
