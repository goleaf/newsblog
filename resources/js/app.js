import './bootstrap';

import Alpine from 'alpinejs';

// Import global stores (always needed)
import themeStore from './stores/theme';
import notificationsStore from './stores/notifications';
import modalStore from './stores/modal';

// Import browser notifications
import './browser-notifications';
import './bookmarks';
import './shortcuts';
import './utils/qrcode';

// UI components
import './components/gallery';
import './components/social-embed';
import './components/chart';
// Widgets
import { initWeatherWidgets } from './widgets/weather';
import { initStockTickerWidgets } from './widgets/stock';
import { initCountdownWidgets } from './widgets/countdown';

window.Alpine = Alpine;

// Register global stores (available on all pages)
Alpine.store('theme', themeStore());
Alpine.store('notifications', notificationsStore());
Alpine.store('modal', modalStore());

// Register theme toggle component (used in header on all pages)
Alpine.data('themeToggle', themeStore);

// Start Alpine
Alpine.start();

// Init widgets
document.addEventListener('DOMContentLoaded', () => {
    initWeatherWidgets();
    initStockTickerWidgets();
    initCountdownWidgets();
});

// Dynamic imports for page-specific functionality
// These will be loaded on-demand based on the page
window.loadPageModule = async (moduleName) => {
    try {
        const module = await import(`./pages/${moduleName}.js`);
        if (module.default && typeof module.default.init === 'function') {
            module.default.init();
        }
    } catch (error) {
        console.error(`Failed to load page module: ${moduleName}`, error);
    }
};

// Register Service Worker for PWA (only in production and if supported)
if ('serviceWorker' in navigator && import.meta.env.PROD) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.warn('Service worker registration failed:', err);
        });
    });
}
