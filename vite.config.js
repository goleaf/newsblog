import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    build: {
        // Optimize chunk size
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                // Manual chunk splitting for better caching
                manualChunks: {
                    'vendor': ['alpinejs'],
                },
            },
        },
        // Enable minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.log in production
                drop_debugger: true,
            },
        },
        // Generate source maps for debugging (optional)
        sourcemap: false,
        // Optimize CSS
        cssMinify: true,
    },
});
