@props(['tags', 'selected' => []])

<div class="mb-4" x-data="{ 
    open: false,
    selectedTags: {{ json_encode($selected) }},
    toggleTag(tagId) {
        const index = this.selectedTags.indexOf(tagId);
        if (index > -1) {
            this.selectedTags.splice(index, 1);
        } else {
            this.selectedTags.push(tagId);
        }
        filters.tags = this.selectedTags;
    },
    isSelected(tagId) {
        return this.selectedTags.includes(tagId);
    },
    getSelectedCount() {
        return this.selectedTags.length;
    }
}">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Tags
    </label>
    
    <!-- Selected Tags Display -->
    <div class="relative">
        <button
            type="button"
            @click="open = !open"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-left focus:ring-2 focus:ring-blue-500 focus:border-transparent flex items-center justify-between"
        >
            <span class="text-gray-900 dark:text-white text-sm">
                <span x-show="getSelectedCount() === 0">Select tags...</span>
                <span x-show="getSelectedCount() > 0" x-text="getSelectedCount() + ' tag(s) selected'"></span>
            </span>
            <svg class="w-5 h-5 text-gray-400" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <!-- Dropdown -->
        <div
            x-show="open"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
            style="display: none;"
        >
            @foreach($tags as $tag)
                <label class="flex items-center px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer">
                    <input
                        type="checkbox"
                        :checked="isSelected({{ $tag->id }})"
                        @change="toggleTag({{ $tag->id }})"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    >
                    <span class="ml-2 text-sm text-gray-900 dark:text-white">{{ $tag->name }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Selected Tags Pills -->
    <div x-show="getSelectedCount() > 0" class="mt-2 flex flex-wrap gap-2">
        @foreach($tags as $tag)
            <span
                x-show="isSelected({{ $tag->id }})"
                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs"
            >
                {{ $tag->name }}
                <button
                    type="button"
                    @click="toggleTag({{ $tag->id }})"
                    class="hover:text-blue-900 dark:hover:text-blue-200"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
        @endforeach
    </div>
</div>
