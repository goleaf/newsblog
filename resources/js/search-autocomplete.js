/**
 * Search Autocomplete Component
 * Provides debounced AJAX autocomplete suggestions for search input
 */

class SearchAutocomplete {
    constructor(inputSelector, options = {}) {
        this.input = document.querySelector(inputSelector);
        if (!this.input) {
            return;
        }

        this.options = {
            minLength: options.minLength || 3,
            debounceDelay: options.debounceDelay || 300,
            maxSuggestions: options.maxSuggestions || 5,
            endpoint: options.endpoint || '/api/v1/search/suggestions',
            ...options,
        };

        this.suggestionsContainer = null;
        this.currentSuggestions = [];
        this.selectedIndex = -1;
        this.debounceTimer = null;
        this.isOpen = false;

        this.init();
    }

    init() {
        this.createSuggestionsContainer();
        this.attachEventListeners();
    }

    createSuggestionsContainer() {
        this.suggestionsContainer = document.createElement('div');
        this.suggestionsContainer.className = 'search-autocomplete absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-auto';
        this.suggestionsContainer.style.display = 'none';
        
        const inputWrapper = this.input.closest('form') || this.input.parentElement;
        inputWrapper.style.position = 'relative';
        inputWrapper.appendChild(this.suggestionsContainer);
    }

    attachEventListeners() {
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeyDown(e));
        this.input.addEventListener('focus', () => {
            if (this.currentSuggestions.length > 0) {
                this.showSuggestions();
            }
        });

        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.suggestionsContainer.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }

    handleInput(e) {
        const query = e.target.value.trim();

        if (query.length < this.options.minLength) {
            this.hideSuggestions();
            return;
        }

        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.fetchSuggestions(query);
        }, this.options.debounceDelay);
    }

    async fetchSuggestions(query) {
        try {
            const url = new URL(this.options.endpoint, window.location.origin);
            url.searchParams.append('q', query);
            url.searchParams.append('limit', this.options.maxSuggestions);

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success && Array.isArray(data.data)) {
                this.currentSuggestions = data.data;
                this.renderSuggestions();
            } else {
                this.currentSuggestions = [];
                this.hideSuggestions();
            }
        } catch (error) {
            console.error('Error fetching suggestions:', error);
            this.currentSuggestions = [];
            this.hideSuggestions();
        }
    }

    renderSuggestions() {
        if (this.currentSuggestions.length === 0) {
            this.hideSuggestions();
            return;
        }

        this.suggestionsContainer.innerHTML = '';

        this.currentSuggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = `suggestion-item px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 ${
                index === this.selectedIndex ? 'bg-gray-100 dark:bg-gray-700' : ''
            }`;
            item.textContent = suggestion;
            item.dataset.index = index;

            item.addEventListener('click', () => {
                this.selectSuggestion(suggestion);
            });

            item.addEventListener('mouseenter', () => {
                this.selectedIndex = index;
                this.updateSelectedItem();
            });

            this.suggestionsContainer.appendChild(item);
        });

        this.showSuggestions();
    }

    showSuggestions() {
        if (this.currentSuggestions.length > 0) {
            this.suggestionsContainer.style.display = 'block';
            this.isOpen = true;
        }
    }

    hideSuggestions() {
        this.suggestionsContainer.style.display = 'none';
        this.isOpen = false;
        this.selectedIndex = -1;
    }

    updateSelectedItem() {
        const items = this.suggestionsContainer.querySelectorAll('.suggestion-item');
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.classList.add('bg-gray-100', 'dark:bg-gray-700');
            } else {
                item.classList.remove('bg-gray-100', 'dark:bg-gray-700');
            }
        });
    }

    handleKeyDown(e) {
        if (!this.isOpen || this.currentSuggestions.length === 0) {
            if (e.key === 'Enter') {
                return; // Allow form submission
            }
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(
                    this.selectedIndex + 1,
                    this.currentSuggestions.length - 1
                );
                this.updateSelectedItem();
                break;

            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                if (this.selectedIndex === -1) {
                    this.hideSuggestions();
                } else {
                    this.updateSelectedItem();
                }
                break;

            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0 && this.selectedIndex < this.currentSuggestions.length) {
                    this.selectSuggestion(this.currentSuggestions[this.selectedIndex]);
                } else if (this.currentSuggestions.length > 0) {
                    this.selectSuggestion(this.currentSuggestions[0]);
                }
                break;

            case 'Escape':
                e.preventDefault();
                this.hideSuggestions();
                break;
        }
    }

    selectSuggestion(suggestion) {
        this.input.value = suggestion;
        this.hideSuggestions();
        
        // Trigger input event to allow form submission
        this.input.dispatchEvent(new Event('input', { bubbles: true }));
        
        // Optionally submit the form
        if (this.options.autoSubmit !== false) {
            const form = this.input.closest('form');
            if (form) {
                form.submit();
            }
        }
    }
}

// Auto-initialize if DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.querySelector('input[name="q"]');
        if (searchInput) {
            window.searchAutocomplete = new SearchAutocomplete('input[name="q"]');
        }
    });
} else {
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput) {
        window.searchAutocomplete = new SearchAutocomplete('input[name="q"]');
    }
}

export default SearchAutocomplete;

