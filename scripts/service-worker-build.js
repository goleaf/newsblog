#!/usr/bin/env node

/**
 * Build a production service worker with Workbox.
 */
import { generateSW } from 'workbox-build';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const publicDir = path.resolve(__dirname, '..', 'public');
const buildDir = path.resolve(publicDir, 'build');

async function buildSW() {
    console.log('🛠  Generating service worker...');
    const { count, size, warnings } = await generateSW({
        globDirectory: publicDir,
        globPatterns: [
            'build/**/*.{js,css,woff2,map}',
            'favicon.ico',
            'manifest.webmanifest',
            'offline.html',
        ],
        swDest: path.join(publicDir, 'sw.js'),
        clientsClaim: true,
        skipWaiting: true,
        sourcemap: false,
        navigateFallback: '/offline',
        navigateFallbackDenylist: [/^\/nova\//, /^\/api\//],
        runtimeCaching: [
            {
                urlPattern: ({ request }) => request.destination === 'style' || request.destination === 'script',
                handler: 'StaleWhileRevalidate',
                options: {
                    cacheName: 'static-assets',
                },
            },
            {
                urlPattern: ({ request }) => request.destination === 'image',
                handler: 'CacheFirst',
                options: {
                    cacheName: 'images',
                    expiration: {
                        maxEntries: 200,
                        maxAgeSeconds: 60 * 60 * 24 * 30,
                    },
                    cacheableResponse: { statuses: [0, 200] },
                },
            },
        ],
    });

    if (warnings?.length) {
        console.warn('Workbox warnings:\n', warnings.join('\n'));
    }
    console.log(`✅ Service worker generated. Precached ${count} files (${(size / 1024).toFixed(1)} KB).`);
}

buildSW().catch((err) => {
    console.error('❌ Failed to generate service worker:', err);
    process.exit(1);
});



