import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['workbench/resources/css/app.css'],
            refresh: true,
            publicDirectory: 'workbench/public',
            buildDirectory: 'build',
        }),
        tailwindcss(),
    ],
});
