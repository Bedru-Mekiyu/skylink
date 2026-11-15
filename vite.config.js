import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Tailwind v3 is processed through PostCSS (see postcss.config.js),
// so the @tailwindcss/vite (v4) plugin is intentionally not used here.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
