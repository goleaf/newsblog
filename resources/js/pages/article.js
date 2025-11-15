// Article page-specific JavaScript
import Alpine from 'alpinejs';

// Import components needed for article pages
import readingProgress from '../components/reading-progress';
import sharePost from '../components/share-post';
import bookmarkButton from '../components/bookmark-button';
import postFeedback from '../components/post-feedback';
import { seriesProgress } from '../series-progress';

// Register article-specific components
Alpine.data('readingProgress', readingProgress);
Alpine.data('sharePost', sharePost);
Alpine.data('bookmarkButton', bookmarkButton);
Alpine.data('postFeedback', postFeedback);
Alpine.data('seriesProgress', seriesProgress);

export default {
    init() {
        // Article page-specific initialization
        console.log('Article page JavaScript loaded');
    }
};
