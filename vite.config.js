import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import viteCompression from 'vite-plugin-compression';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/critical.css',
                'resources/css/critical-home.css',
                'resources/css/critical-article.css',
                'resources/css/print.css',
                'resources/js/app.js',
                // Page-specific entry points for code splitting
                'resources/js/pages/homepage.js',
                'resources/js/pages/article.js',
                'resources/js/pages/dashboard.js',
                'resources/js/pages/search.js',
                'resources/js/pages/analytics-dashboard.js',
            ],
            refresh: true,
        }),
        // Generate gzip and brotli compressed assets for production
        viteCompression({
            verbose: false,
            disable: process.env.NODE_ENV !== 'production',
            algorithm: 'gzip',
            ext: '.gz',
            threshold: 1024,
        }),
        viteCompression({
            verbose: false,
            disable: process.env.NODE_ENV !== 'production',
            algorithm: 'brotliCompress',
            ext: '.br',
            threshold: 1024,
        }),
    ],
    build: {
        // Optimize chunk size
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                treeshake: true,
                // Manual chunk splitting for better caching
                manualChunks(id) {
                    // Vendor chunk for Alpine.js and other dependencies
                    if (id.includes('node_modules')) {
                        if (id.includes('alpinejs')) {
                            return 'vendor-alpine';
                        }
                        if (id.includes('axios')) {
                            return 'vendor-axios';
                        }
                        // Other node_modules go into general vendor chunk
                        return 'vendor';
                    }
                    
                    // Separate chunks for stores (shared across pages)
                    if (id.includes('/stores/')) {
                        return 'stores';
                    }
                    
                    // Separate chunks for components (lazy loaded)
                    if (id.includes('/components/')) {
                        return 'components';
                    }
                    
                    // Page-specific chunks are handled by entry points
                    // Homepage components
                    if (id.includes('pages/homepage')) {
                        return 'page-homepage';
                    }
                    
                    // Article page components
                    if (id.includes('pages/article')) {
                        return 'page-article';
                    }
                    
                    // Dashboard components
                    if (id.includes('pages/dashboard')) {
                        return 'page-dashboard';
                    }
                    
                    // Search page components
                    if (id.includes('pages/search')) {
                        return 'page-search';
                    }
                },
                // Optimize chunk file names
                chunkFileNames: 'js/[name]-[hash].js',
                entryFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name.endsWith('.css')) {
                        return 'css/[name]-[hash][extname]';
                    }
                    return 'assets/[name]-[hash][extname]';
                },
            },
        },
        // Enable minification using esbuild (default)
        minify: 'esbuild',
        // Generate source maps for debugging (optional)
        sourcemap: false,
        // Optimize CSS
        cssMinify: true,
        // Target modern browsers for smaller bundles
        target: 'es2020',
    },
    // Remove console statements and debuggers in production builds
    esbuild: {
        drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
        legalComments: 'none',
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['alpinejs', 'axios'],
        exclude: [],
    },
});
