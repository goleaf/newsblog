import './bootstrap';

import Alpine from 'alpinejs';
import infiniteScroll from './components/infinite-scroll';

window.Alpine = Alpine;

// Register Alpine components
Alpine.data('infiniteScroll', infiniteScroll);

Alpine.start();

// Import search autocomplete
import './search-autocomplete';
