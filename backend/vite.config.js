import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/stores.js', 'resources/js/landing-map.js', 'resources/js/cake-preview.js', 'resources/js/location-picker.js', 'resources/js/ph-address-picker.js', 'resources/js/delivery-address-map.js', 'resources/js/native-payments.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
