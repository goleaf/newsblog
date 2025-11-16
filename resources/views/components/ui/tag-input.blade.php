@props([
    'name' => 'tags',
    'selected' => [],
    'placeholder' => 'Add tags...',
    'maxTags' => 10,
])

@php
    $selectedTags = is_array($selected) ? $selected : (is_string($selected) ? explode(',', $selected) : []);
@endphp

<div 
    x-data="tagInput({
        name: '{{ $name }}',
        selected: {{ json_encode($selectedTags) }},
        maxTags: {{ $maxTags }},
        placeholder: '{{ $placeholder }}'
    })"
    class="relative"
>
    <!-- Selected Tags Display -->
    <div class="flex flex-wrap gap-2 mb-2" x-show="tags.length > 0">
        <template x-for="(tag, index) in tags" :key="index">
            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full text-sm font-medium">
                <span class="text-indigo-500 dark:text-indigo-400">#</span>
                <span x-text="tag"></span>
                <button 
                    type="button"
                    @click="removeTag(index)"
                    class="ml-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200 focus:outline-none"
                    :aria-label="'Remove ' + tag + ' tag'"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <input type="hidden" :name="name + '[]'" :value="tag">
            </div>
        </template>
    </div>

    <!-- Input Field with Autocomplete -->
    <div class="relative">
        <input 
            type="text"
            x-model="input"
            @input="searchTags"
            @keydown.enter.prevent="addTag"
            @keydown.comma.prevent="addTag"
            @keydown.down.prevent="navigateDown"
            @keydown.up.prevent="navigateUp"
            @keydown.escape="closeSuggestions"
            @focus="showSuggestions = true"
            @blur="handleBlur"
            :placeholder="tags.length >= maxTags ? 'Maximum tags reached' : placeholder"
            :disabled="tags.length >= maxTags"
            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
        
        <!-- Autocomplete Suggestions -->
        <div 
            x-show="showSuggestions && suggestions.length > 0"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-auto"
        >
            <template x-for="(suggestion, index) in suggestions" :key="suggestion.slug">
                <button
                    type="button"
                    @click="selectSuggestion(suggestion.name)"
                    @mouseenter="selectedIndex = index"
                    :class="{
                        'bg-indigo-50 dark:bg-indigo-900/30': selectedIndex === index
                    }"
                    class="w-full px-4 py-2 text-left hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors flex items-center justify-between"
                >
                    <span class="flex items-center gap-2 text-gray-900 dark:text-white">
                        <span class="text-indigo-500 dark:text-indigo-400">#</span>
                        <span x-text="suggestion.name"></span>
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="suggestion.posts_count + ' articles'"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Helper Text -->
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
        Press Enter or comma to add a tag. <span x-text="tags.length"></span>/<span x-text="maxTags"></span> tags
    </p>
</div>

@push('scripts')
<script>
function tagInput(config) {
    return {
        name: config.name,
        tags: config.selected || [],
        input: '',
        suggestions: [],
        showSuggestions: false,
        selectedIndex: -1,
        maxTags: config.maxTags || 10,
        placeholder: config.placeholder || 'Add tags...',
        searchTimeout: null,

        init() {
            // Initialize component
        },

        searchTags() {
            clearTimeout(this.searchTimeout);
            
            if (this.input.trim().length < 2) {
                this.suggestions = [];
                return;
            }

            this.searchTimeout = setTimeout(() => {
                fetch(`/api/tags/search?q=${encodeURIComponent(this.input.trim())}`)
                    .then(response => response.json())
                    .then(data => {
                        // Filter out already selected tags
                        this.suggestions = data.filter(tag => 
                            !this.tags.includes(tag.name)
                        );
                        this.selectedIndex = -1;
                    })
                    .catch(error => {
                        console.error('Error searching tags:', error);
                        this.suggestions = [];
                    });
            }, 300);
        },

        addTag() {
            const tagName = this.input.trim();
            
            if (!tagName) return;
            
            if (this.tags.length >= this.maxTags) {
                alert(`Maximum ${this.maxTags} tags allowed`);
                return;
            }

            if (this.tags.includes(tagName)) {
                this.input = '';
                return;
            }

            this.tags.push(tagName);
            this.input = '';
            this.suggestions = [];
            this.showSuggestions = false;
        },

        selectSuggestion(tagName) {
            if (this.tags.length >= this.maxTags) {
                alert(`Maximum ${this.maxTags} tags allowed`);
                return;
            }

            if (!this.tags.includes(tagName)) {
                this.tags.push(tagName);
            }
            
            this.input = '';
            this.suggestions = [];
            this.showSuggestions = false;
        },

        removeTag(index) {
            this.tags.splice(index, 1);
        },

        navigateDown() {
            if (this.suggestions.length === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % this.suggestions.length;
        },

        navigateUp() {
            if (this.suggestions.length === 0) return;
            this.selectedIndex = this.selectedIndex <= 0 
                ? this.suggestions.length - 1 
                : this.selectedIndex - 1;
        },

        handleBlur() {
            // Delay to allow click on suggestion
            setTimeout(() => {
                this.showSuggestions = false;
            }, 200);
        },

        closeSuggestions() {
            this.showSuggestions = false;
            this.selectedIndex = -1;
        }
    }
}
</script>
@endpush
