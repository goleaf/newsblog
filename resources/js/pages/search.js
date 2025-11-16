// Search page-specific JavaScript
import Alpine from 'alpinejs';

// Import search-specific functionality
import '../search-autocomplete';
import '../search-click-tracking';

export default {
    init() {
        // Search page-specific initialization
        const checkbox = document.getElementById('showRelevanceScores');
        const scores = document.querySelectorAll('.relevance-score');

        if (checkbox && scores.length > 0) {
            checkbox.addEventListener('change', function () {
                scores.forEach((score) => {
                    if (checkbox.checked) {
                        score.classList.remove('hidden');
                    } else {
                        score.classList.add('hidden');
                    }
                });
            });
        }
    }
};
