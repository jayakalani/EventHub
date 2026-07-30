import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/admin-reports.js',
                'resources/js/admin-dashboard.js',
                'resources/js/organizer-dashboard.js',
                'resources/js/organizer-reports.js',
                'resources/js/cro-reports.js',
                'resources/js/cro-dashboard.js',
            ],
            refresh: true,
        }),
    ],
});
