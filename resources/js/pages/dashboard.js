// Dashboard page-specific JavaScript
import Alpine from 'alpinejs';

// Import components needed for dashboard
import bookmarkButton from '../components/bookmark-button';

// Register dashboard-specific components
Alpine.data('bookmarkButton', bookmarkButton);

export default {
    init() {
        // Dashboard-specific initialization
        console.log('Dashboard JavaScript loaded');
    }
};
