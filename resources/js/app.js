import './bootstrap';

import Alpine from 'alpinejs';

// Import global stores
import themeStore from './stores/theme';
import notificationsStore from './stores/notifications';
import modalStore from './stores/modal';

// Import existing components
import infiniteScroll from './components/infinite-scroll';
import readingProgress from './components/reading-progress';
import sharePost from './components/share-post';
import bookmarkButton from './components/bookmark-button';
import postFeedback from './components/post-feedback';
import { seriesProgress } from './series-progress';

window.Alpine = Alpine;

// Register global stores
Alpine.store('theme', themeStore());
Alpine.store('notifications', notificationsStore());
Alpine.store('modal', modalStore());

// Register existing components
Alpine.data('infiniteScroll', infiniteScroll);
Alpine.data('readingProgress', readingProgress);
Alpine.data('sharePost', sharePost);
Alpine.data('bookmarkButton', bookmarkButton);
Alpine.data('postFeedback', postFeedback);
Alpine.data('seriesProgress', seriesProgress);

// Register new components
Alpine.data('themeToggle', themeStore);

Alpine.start();

import './search-autocomplete';
