// Homepage-specific JavaScript
import Alpine from 'alpinejs';

// Import components needed for homepage
import infiniteScroll from '../components/infinite-scroll';

// Register homepage-specific components
Alpine.data('infiniteScroll', infiniteScroll);

export default {
    init() {
        // Homepage-specific initialization
        console.log('Homepage JavaScript loaded');
    }
};
